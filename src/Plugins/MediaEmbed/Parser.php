<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;
use s9e\TextFormatter\Parser as TagStack;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Utils\Http;
class Parser extends ParserBase
{
	protected static $client;
	protected static $clientCacheDir;
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
		{
			$tagName = $this->config['tagName'];
			$url     = $m[0][0];
			$pos     = $m[0][1];
			$len     = \strlen($url);
			$this->parser->addSelfClosingTag($tagName, $pos, $len, -10)->setAttribute('url', $url);
		}
	}
	public static function filterTag(Tag $tag, TagStack $tagStack, array $hosts, array $sites, $cacheDir)
	{
		$tag->invalidate();
		if ($tag->hasAttribute('url'))
		{
			$url    = $tag->getAttribute('url');
			$siteId = self::getSiteIdFromUrl($url, $hosts);
			if (isset($sites[$siteId]))
			{
				$attributes = self::getAttributes($url, $sites[$siteId], $cacheDir);
				if (!empty($attributes))
					self::createTag(\strtoupper($siteId), $tagStack, $tag)->setAttributes($attributes);
			}
		}
	}
	protected static function addNamedCaptures(array &$attributes, $string, array $regexps)
	{
		$matched = 0;
		foreach ($regexps as $_53d26d37)
		{
			list($regexp, $map) = $_53d26d37;
			$matched += \preg_match($regexp, $string, $m);
			foreach ($map as $i => $name)
				if (isset($m[$i]) && $m[$i] !== '' && $name !== '')
					$attributes[$name] = $m[$i];
		}
		return (bool) $matched;
	}
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
	protected static function getAttributes($url, array $config, $cacheDir)
	{
		$attributes = [];
		self::addNamedCaptures($attributes, $url, $config[0]);
		foreach ($config[1] as $scrapeConfig)
			self::scrape($attributes, $url, $scrapeConfig, $cacheDir);
		return $attributes;
	}
	protected static function getHttpClient($cacheDir)
	{
		if (!isset(self::$client) || self::$clientCacheDir !== $cacheDir)
		{
			self::$client = (isset($cacheDir)) ? Http::getCachingClient($cacheDir) : Http::getClient();
			self::$clientCacheDir = $cacheDir;
		}
		return self::$client;
	}
	protected static function getSiteIdFromUrl($url, array $hosts)
	{
		$host = (\preg_match('(^https?://([^/]+))', \strtolower($url), $m)) ? $m[1] : '';
		while ($host > '')
		{
			if (isset($hosts[$host]))
				return $hosts[$host];
			$host = \preg_replace('(^[^.]*.)', '', $host);
		}
		return '';
	}
	protected static function interpolateVars($str, array $vars)
	{
		return \preg_replace_callback(
			'(\\{@(\\w+)\\})',
			function ($m) use ($vars)
			{
				return (isset($vars[$m[1]])) ? $vars[$m[1]] : '';
			},
			$str
		);
	}
	protected static function scrape(array &$attributes, $url, array $config, $cacheDir)
	{
		$vars = [];
		if (self::addNamedCaptures($vars, $url, $config['match']))
		{
			if (isset($config['url']))
				$url = self::interpolateVars($config['url'], $vars + $attributes);
			if (\preg_match('(^https?://[^#]+)i', $url, $m))
			{
				$response = self::wget($m[0], $cacheDir, $config);
				self::addNamedCaptures($attributes, $response, $config['extract']);
			}
		}
	}
	protected static function wget($url, $cacheDir, $config)
	{
		$headers = (isset($config['header'])) ? (array) $config['header'] : [];
		return @self::getHttpClient($cacheDir)->get($url, $headers);
	}
}