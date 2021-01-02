<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;

use s9e\TextFormatter\Parser as TagStack;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Utils\Http;

class Parser extends ParserBase
{
	/**
	* @var \s9e\TextFormatter\Utils\Http\Client Client used to perform HTTP request
	*/
	protected static $client;

	/**
	* @var string|null Cache dir used by cached client
	*/
	protected static $clientCacheDir;

	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
		{
			$tagName = $this->config['tagName'];
			$url     = $m[0][0];
			$pos     = $m[0][1];
			$len     = strlen($url);

			// Give that tag priority over other tags such as Autolink's
			$this->parser->addSelfClosingTag($tagName, $pos, $len, -10)->setAttribute('url', $url);
		}
	}

	/**
	* Filter a MEDIA tag
	*
	* This will always invalidate the original tag, and possibly replace it with the tag that
	* corresponds to the media site
	*
	* @param  Tag         $tag      The original tag
	* @param  TagStack    $tagStack Parser instance, so that we can add the new tag to the stack
	* @param  array       $hosts    Map of [hostname => siteId]
	* @param  array       $sites    Map of [siteId => siteConfig]
	* @param  string|null $cacheDir Path to the cache directory
	* @return void
	*/
	public static function filterTag(Tag $tag, TagStack $tagStack, array $hosts, array $sites, $cacheDir)
	{
		// Always invalidate this tag
		$tag->invalidate();

		if ($tag->hasAttribute('url'))
		{
			$url    = $tag->getAttribute('url');
			$siteId = self::getSiteIdFromUrl($url, $hosts);
			if (isset($sites[$siteId]))
			{
				$attributes = self::getAttributes($url, $sites[$siteId], $cacheDir);
				if (!empty($attributes))
				{
					self::createTag(strtoupper($siteId), $tagStack, $tag)->setAttributes($attributes);
				}
			}
		}
	}

	/**
	* Add named captures from a set of regular expressions to a set of attributes
	*
	* @param  array   &$attributes Associative array of strings
	* @param  string   $string     Text to match
	* @param  array[]  $regexps    List of [regexp, map] pairs
	* @return bool                 Whether any regexp matched
	*/
	protected static function addNamedCaptures(array &$attributes, $string, array $regexps)
	{
		$matched = 0;
		foreach ($regexps as list($regexp, $map))
		{
			$matched += preg_match($regexp, $string, $m);
			foreach ($map as $i => $name)
			{
				if (isset($m[$i]) && $m[$i] !== '' && $name !== '')
				{
					$attributes[$name] = $m[$i];
				}
			}
		}

		return (bool) $matched;
	}

	/**
	* Create a tag for a media embed
	*
	* @param  string   $tagName  Tag's name
	* @param  TagStack $tagStack
	* @param  Tag      $tag      Reference tag
	* @return Tag                New tag
	*/
	protected static function createTag($tagName, TagStack $tagStack, Tag $tag)
	{
		$startPos = $tag->getPos();
		$endTag   = $tag->getEndTag();
		if ($endTag)
		{
			$startLen = $tag->getLen();
			$endPos   = $endTag->getPos();
			$endLen   = $endTag->getLen();
		}
		else
		{
			$startLen = 0;
			$endPos   = $tag->getPos() + $tag->getLen();
			$endLen   = 0;
		}

		return $tagStack->addTagPair($tagName, $startPos, $startLen, $endPos, $endLen, $tag->getSortPriority());
	}

	/**
	* Return a set of attributes for given URL based on a site's config
	*
	* @param  string      $url      Original URL
	* @param  array       $config   Site config
	* @param  string|null $cacheDir Path to the cache directory
	* @return array                 Associative array of attributes
	*/
	protected static function getAttributes($url, array $config, $cacheDir)
	{
		$attributes = [];
		self::addNamedCaptures($attributes, $url, $config[0]);
		foreach ($config[1] as $scrapeConfig)
		{
			self::scrape($attributes, $url, $scrapeConfig, $cacheDir);
		}

		return $attributes;
	}

	/**
	* Return a cached instance of the HTTP client
	*
	* @param  string|null $cacheDir
	* @return \s9e\TextFormatter\Utils\Http\Client
	*/
	protected static function getHttpClient($cacheDir)
	{
		if (!isset(self::$client) || self::$clientCacheDir !== $cacheDir)
		{
			self::$client = (isset($cacheDir)) ? Http::getCachingClient($cacheDir) : Http::getClient();
			self::$clientCacheDir = $cacheDir;
		}

		return self::$client;
	}

	/**
	* Return the siteId that corresponds to given URL
	*
	* @param  string  $url   Original URL
	* @param  array   $hosts Map of [hostname => siteId]
	* @return string         URL's siteId, or an empty string
	*/
	protected static function getSiteIdFromUrl($url, array $hosts)
	{
		$host = (preg_match('(^https?://([^/]+))', strtolower($url), $m)) ? $m[1] : '';
		while ($host > '')
		{
			if (isset($hosts[$host]))
			{
				return $hosts[$host];
			}
			$host = preg_replace('(^[^.]*.)', '', $host);
		}

		return '';
	}

	/**
	* Interpolate {@vars} in given string
	*
	* @param  string $str  Original string
	* @param  array  $vars Associative array
	* @return string       Interpolated string
	*/
	protected static function interpolateVars($str, array $vars)
	{
		return preg_replace_callback(
			'(\\{@(\\w+)\\})',
			function ($m) use ($vars)
			{
				return (isset($vars[$m[1]])) ? $vars[$m[1]] : '';
			},
			$str
		);
	}

	/**
	* Scrape values and add them to current attributes
	*
	* @param  array       &$attributes Attributes
	* @param  string       $url        Original URL
	* @param  array        $config     Scraping config
	* @param  string|null  $cacheDir   Path to the cache directory
	* @return void
	*/
	protected static function scrape(array &$attributes, $url, array $config, $cacheDir)
	{
		$vars = [];
		if (self::addNamedCaptures($vars, $url, $config['match']))
		{
			if (isset($config['url']))
			{
				$url = self::interpolateVars($config['url'], $vars + $attributes);
			}
			if (preg_match('(^https?://[^#]+)i', $url, $m))
			{
				$response = self::wget($m[0], $cacheDir, $config);
				self::addNamedCaptures($attributes, $response, $config['extract']);
			}
		}
	}

	/**
	* Retrieve external content
	*
	* @param  string      $url      URL
	* @param  string|null $cacheDir Path to the cache directory
	* @param  array       $config   Scraping config
	* @return string                Response body
	*/
	protected static function wget($url, $cacheDir, $config)
	{
		$options = [
			'headers' => (isset($config['header'])) ? (array) $config['header'] : []
		];

		return @self::getHttpClient($cacheDir)->get($url, $options);
	}
}