<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;

use s9e\TextFormatter\Parser as TagStack;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
		{
			$url = $m[0][0];
			$pos = $m[0][1];
			$len = strlen($url);

			$tag = $this->parser->addSelfClosingTag('MEDIA', $pos, $len);
			$tag->setAttribute('url', $url);

			// Give that tag priority over other tags such as Autolink's
			$tag->setSortPriority(-10);
		}
	}

	/**
	* Filter a MEDIA tag
	*
	* This will always invalidate the original tag, and possibly replace it with the tag that
	* corresponds to the media site
	*
	* @param  Tag      $tag      The original tag
	* @param  TagStack $tagStack Parser instance, so that we can add the new tag to the stack
	* @param  array    $sites    Map of [host => siteId]
	* @return bool               Unconditionally FALSE
	*/
	public static function filterTag(Tag $tag, TagStack $tagStack, array $sites)
	{
		if ($tag->hasAttribute('media'))
		{
			// [media=youtube]xxxxxxx[/media]
			$tagName = $tag->getAttribute('media');

			// If this tag doesn't have an id attribute, copy the value of the url attribute, so
			// that the tag acts like [media=youtube id=xxxx]xxxx[/media]
			if (!$tag->hasAttribute('id') && $tag->hasAttribute('url'))
			{
				$tag->setAttribute('id', $tag->getAttribute('url'));
			}
		}
		elseif ($tag->hasAttribute('url'))
		{
			// Capture the host part of the URL
			$p = parse_url($tag->getAttribute('url'));

			if ($p && isset($p['host']))
			{
				$host = $p['host'];

				// Start with the full host then pop domain labels off the start until we get a
				// match
				do
				{
					if (isset($sites[$host]))
					{
						$tagName = $sites[$host];
						break;
					}

					$pos = strpos($host, '.');
					if ($pos === false)
					{
						break;
					}

					$host = substr($host, 1 + $pos);
				}
				while ($host > '');
			}
		}

		if (isset($tagName))
		{
			$endTag = $tag->getEndTag() ?: $tag;

			// Compute the boundaries of our new tag
			$lpos = $tag->getPos();
			$rpos = $endTag->getPos() + $endTag->getLen();

			// Create a new tag and copy this tag's attributes
			$tagStack->addSelfClosingTag(strtoupper($tagName), $lpos, $rpos - $lpos)
			         ->setAttributes($tag->getAttributes());
		}

		return false;
	}

	/**
	* Scrape the content of an URL to extract some data
	*
	* @param  Tag    $tag          Source tag
	* @param  array  $scrapeConfig Array of scrape directives
	* @param  string $cacheDir     Path to the cache directory
	* @return bool                 Unconditionally TRUE
	*/
	public static function scrape(Tag $tag, array $scrapeConfig, $cacheDir = null)
	{
		if (!$tag->hasAttribute('url'))
		{
			return true;
		}

		// Ensure that the URL is valid
		$url = filter_var($tag->getAttribute('url'), FILTER_VALIDATE_URL);
		if (!preg_match('#^https?://#', $url))
		{
			// A bad URL means we don't scrape, but it doesn't necessarily invalidate the tag
			return true;
		}

		foreach ($scrapeConfig as $scrape)
		{
			list($matchRegexps, $extractRegexps, $attrNames) = $scrape;

			// Test whether this scrape would help fill any attribute
			$skip = true;
			foreach ($attrNames as $attrName)
			{
				if (!$tag->hasAttribute($attrName))
				{
					$skip = false;
					break;
				}
			}
			if ($skip)
			{
				continue;
			}

			// Test whether this URL matches any regexp
			$vars = [];
			$skip = true;
			foreach ((array) $matchRegexps as $matchRegexp)
			{
				if (preg_match($matchRegexp, $url, $m))
				{
					$vars += $m;
					$skip = false;
				}
			}
			if ($skip)
			{
				continue;
			}

			// Generate the URL used for scraping. Use the one stored in the config if applicable,
			// or look into the tag otherwise
			if (isset($scrape[3]))
			{
				// Add the tag's attributes to the named captures from the "match" regexp
				$vars += $tag->getAttributes();

				// Replace {@var} tokens in the URL
				$scrapeUrl = preg_replace_callback(
					'#\\{@(\\w+)\\}#',
					function ($m) use ($vars)
					{
						return (isset($vars[$m[1]])) ? $vars[$m[1]] : '';
					},
					$scrape[3]
				);
			}
			else
			{
				// Use the same URL for scraping
				$scrapeUrl = $url;
			}

			$content = self::wget($scrapeUrl, $cacheDir);

			// Execute the extract regexps and fill any missing attribute
			foreach ((array) $extractRegexps as $extractRegexp)
			{
				if (preg_match($extractRegexp, $content, $m))
				{
					foreach ($attrNames as $attrName)
					{
						if (isset($m[$attrName]) && !$tag->hasAttribute($attrName))
						{
							$tag->setAttribute($attrName, $m[$attrName]);
						}
					}
				}
			}
		}

		return true;
	}

	/**
	* Retrieve external content (possibly from the cache)
	*
	* If the cache directory exists, the external content will be saved into it. Cached content is
	* never pruned
	*
	* @param  string $url      URL
	* @param  string $cacheDir Path to the cache directory
	* @return string           External content
	*/
	protected static function wget($url, $cacheDir)
	{
		$prefix = $suffix = $context = null;
		if (extension_loaded('zlib'))
		{
			$prefix  = 'compress.zlib://';
			$suffix  = '.gz';
			$context = stream_context_create(['http' => ['header' => 'Accept-Encoding: gzip']]);
		}

		// Return the content from the cache if applicable
		if (isset($cacheDir) && file_exists($cacheDir))
		{
			$cacheFile = $cacheDir . '/http.' . crc32($url) . $suffix;

			if (file_exists($cacheFile))
			{
				return file_get_contents($prefix . $cacheFile);
			}
		}

		// Retrieve the external content from the source
		$content = file_get_contents($prefix . $url, false, $context);

		// Save to the cache if applicable
		if (isset($cacheFile))
		{
			file_put_contents($prefix . $cacheFile, $content);
		}

		return $content;
	}
}