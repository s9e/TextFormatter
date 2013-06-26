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

			$this->parser->addSelfClosingTag('MEDIA', $pos, $len)->setAttribute('url', $url);
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
	* @return bool               Always false
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
}