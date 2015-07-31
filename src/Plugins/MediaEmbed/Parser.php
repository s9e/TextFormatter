<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed;
use s9e\TextFormatter\Parser as TagStack;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;
class Parser extends ParserBase
{
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
		{
			$url = $m[0][0];
			$pos = $m[0][1];
			$len = \strlen($url);
			$tag = $this->parser->addSelfClosingTag('MEDIA', $pos, $len);
			$tag->setAttribute('url', $url);
			$tag->setSortPriority(-10);
		}
	}
	public static function filterTag(Tag $tag, TagStack $tagStack, array $sites)
	{
		if ($tag->hasAttribute('media'))
		{
			$tagName = $tag->getAttribute('media');
			if (!$tag->hasAttribute('id')
			 && $tag->hasAttribute('url')
			 && \strpos($tag->getAttribute('url'), '://') === \false)
				$tag->setAttribute('id', $tag->getAttribute('url'));
		}
		elseif ($tag->hasAttribute('url'))
		{
			$p = \parse_url($tag->getAttribute('url'));
			if (isset($p['scheme']) && isset($sites[$p['scheme'] . ':']))
				$tagName = $sites[$p['scheme'] . ':'];
			elseif (isset($p['host']))
			{
				$host = $p['host'];
				do
				{
					if (isset($sites[$host]))
					{
						$tagName = $sites[$host];
						break;
					}
					$pos = \strpos($host, '.');
					if ($pos === \false)
						break;
					$host = \substr($host, 1 + $pos);
				}
				while ($host > '');
			}
		}
		if (isset($tagName))
		{
			$endTag = $tag->getEndTag() ?: $tag;
			$lpos = $tag->getPos();
			$rpos = $endTag->getPos() + $endTag->getLen();
			$newTag = $tagStack->addSelfClosingTag(\strtoupper($tagName), $lpos, $rpos - $lpos);
			$newTag->setAttributes($tag->getAttributes());
			$newTag->setSortPriority($tag->getSortPriority());
		}
		return \false;
	}
	public static function hasNonDefaultAttribute(Tag $tag)
	{
		foreach ($tag->getAttributes() as $attrName => $void)
			if ($attrName !== 'url')
				return \true;
		return \false;
	}
	public static function scrape(Tag $tag, array $scrapeConfig, $cacheDir = \null)
	{
		if (!$tag->hasAttribute('url'))
			return \true;
		$url = $tag->getAttribute('url');
		if (!\preg_match('#^https?://[^<>"\'\\s]+$#D', $url))
			return \true;
		foreach ($scrapeConfig as $scrape)
			self::scrapeEntry($url, $tag, $scrape, $cacheDir);
		return \true;
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
		$vars    = array();
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
		$prefix = $suffix = $context = \null;
		if (\extension_loaded('zlib'))
		{
			$prefix  = 'compress.zlib://';
			$suffix  = '.gz';
			$context = \stream_context_create(
				array(
					'http' => array('header' => 'Accept-Encoding: gzip'),
					'ssl'  => array('verify_peer' => \false)
				)
			);
		}
		if (isset($cacheDir) && \file_exists($cacheDir))
		{
			$cacheFile = $cacheDir . '/http.' . \crc32($url) . $suffix;
			if (\file_exists($cacheFile))
				return \file_get_contents($prefix . $cacheFile);
		}
		$content = @\file_get_contents($prefix . $url, \false, $context);
		if (isset($cacheFile) && $content !== \false)
			\file_put_contents($prefix . $cacheFile, $content);
		return $content;
	}
}