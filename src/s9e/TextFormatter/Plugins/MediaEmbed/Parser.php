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
			// Match the start of a URL, keep only the last two parts of the hostname
			$regexp = '#//(?:[^/]*\\.)?([^./]+\\.[^/]+)#';
			$url    = $tag->getAttribute('url');

			if (preg_match($regexp, $url, $m))
			{
				$host = $m[1];
				if (isset($sites[$host]))
				{
					$tagName = $sites[$host];
				}
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
	* @param  Tag   $tag          Source tag
	* @param  array $scrapeConfig Array of scrape directives
	* @return bool                Unconditionally TRUE
	*/
	public static function scrape(Tag $tag, array $scrapeConfig)
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
			list($matchRegexp, $extractRegexp, $attrNames) = $scrape;

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

			// Test whether we should skip this URL and whether it matches our regexp
			if ($skip || !preg_match($matchRegexp, $url))
			{
				continue;
			}

			// Scrape the content of this URL
			if (!isset($content))
			{
				$content = file_get_contents(
					'compress.zlib://' . $url,
					false,
					stream_context_create(array(
						'http' => array(
							'header' => 'Accept-Encoding: gzip'
						)
					))
				);
			}

			// Execute the extract regexp and fill any missing attribute
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

		return true;
	}
}