<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2018 The s9e Authors
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
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
		{
			$url = $m[0][0];
			$pos = $m[0][1];
			$len = \strlen($url);
			$tag = $this->parser->addSelfClosingTag($this->config['tagName'], $pos, $len, -10);
			$tag->setAttribute('url', $url);
		}
	}
	public static function filterTag(Tag $tag, TagStack $tagStack, array $sites)
	{
		$tag->invalidate();
		if ($tag->hasAttribute('site'))
			self::addTagFromMediaId($tag, $tagStack, $sites);
		elseif ($tag->hasAttribute('url'))
			self::addTagFromMediaUrl($tag, $tagStack, $sites);
	}
	public static function hasNonDefaultAttribute(Tag $tag)
	{
		foreach ($tag->getAttributes() as $attrName => $void)
			if ($attrName !== 'url')
				return;
		$tag->invalidate();
	}
	public static function scrape(Tag $tag, array $scrapeConfig, $cacheDir = \null)
	{
		if ($tag->hasAttribute('url'))
		{
			$url = $tag->getAttribute('url');
			if (\preg_match('#^https?://[^<>"\'\\s]+$#Di', $url))
			{
				$url = \strtolower(\substr($url, 0, 5)) . \substr($url, 5);
				foreach ($scrapeConfig as $scrape)
					self::scrapeEntry($url, $tag, $scrape, $cacheDir);
			}
		}
		return \true;
	}
	protected static function addSiteTag(Tag $tag, TagStack $tagStack, $siteId)
	{
		$endTag = $tag->getEndTag();
		if ($endTag)
		{
			$startPos = $tag->getPos();
			$startLen = $tag->getLen();
			$endPos   = $endTag->getPos();
			$endLen   = $endTag->getLen();
		}
		else
		{
			$startPos = $tag->getPos();
			$startLen = 0;
			$endPos   = $tag->getPos() + $tag->getLen();
			$endLen   = 0;
		}
		$tagStack->addTagPair(\strtoupper($siteId), $startPos, $startLen, $endPos, $endLen, $tag->getSortPriority())->setAttributes($tag->getAttributes());
	}
	protected static function addTagFromMediaId(Tag $tag, TagStack $tagStack, array $sites)
	{
		$siteId = \strtolower($tag->getAttribute('site'));
		if (\in_array($siteId, $sites, \true))
			self::addSiteTag($tag, $tagStack, $siteId);
	}
	protected static function addTagFromMediaUrl(Tag $tag, TagStack $tagStack, array $sites)
	{
		if (\preg_match('(^\\w+://(?:[^@/]*@)?([^/]+))', $tag->getAttribute('url'), $m))
			$siteId = self::findSiteIdByHost($m[1], $sites);
		if (!empty($siteId))
			self::addSiteTag($tag, $tagStack, $siteId);
	}
	protected static function findSiteIdByHost($host, array $sites)
	{
		do
		{
			if (isset($sites[$host]))
				return $sites[$host];
			$pos = \strpos($host, '.');
			if ($pos === \false)
				break;
			$host = \substr($host, 1 + $pos);
		}
		while ($host > '');
		return \false;
	}
	protected static function getHttpClient($cacheDir)
	{
		if (!isset(self::$client))
			self::$client = (isset($cacheDir)) ? Http::getCachingClient($cacheDir) : Http::getClient();
		return self::$client;
	}
	protected static function replaceTokens($url, array $vars)
	{
		return \preg_replace_callback(
			'#\\{@(\\w+)\\}#',
			function ($m) use ($vars)
			{
				return (isset($vars[$m[1]])) ? $vars[$m[1]] : '';
			},
			$url
		);
	}
	protected static function scrapeEntry($url, Tag $tag, array $scrape, $cacheDir)
	{
		list($matchRegexps, $extractRegexps, $attrNames) = $scrape;
		if (!self::tagIsMissingAnyAttribute($tag, $attrNames))
			return;
		$vars    = [];
		$matched = \false;
		foreach ((array) $matchRegexps as $matchRegexp)
			if (\preg_match($matchRegexp, $url, $m))
			{
				$vars   += $m;
				$matched = \true;
			}
		if (!$matched)
			return;
		$vars += $tag->getAttributes();
		$scrapeUrl = (isset($scrape[3])) ? self::replaceTokens($scrape[3], $vars) : $url;
		self::scrapeUrl($scrapeUrl, $tag, (array) $extractRegexps, $cacheDir);
	}
	protected static function scrapeUrl($url, Tag $tag, array $regexps, $cacheDir)
	{
		$content = self::wget($url, $cacheDir);
		foreach ($regexps as $regexp)
			if (\preg_match($regexp, $content, $m))
				foreach ($m as $k => $v)
					if (!\is_numeric($k) && !$tag->hasAttribute($k))
						$tag->setAttribute($k, $v);
	}
	protected static function tagIsMissingAnyAttribute(Tag $tag, array $attrNames)
	{
		foreach ($attrNames as $attrName)
			if (!$tag->hasAttribute($attrName))
				return \true;
		return \false;
	}
	protected static function wget($url, $cacheDir = \null)
	{
		$url = \preg_replace('(#.*)s', '', $url);
		return @self::getHttpClient($cacheDir)->get($url, ['User-Agent: PHP (not Mozilla)']);
	}
}