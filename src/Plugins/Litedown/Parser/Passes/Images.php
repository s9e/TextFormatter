<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\Litedown\Parser\LinkAttributesSetter;
class Images extends AbstractPass
{
	protected function setLinkAttributes(Tag $tag, $linkInfo, $attrName)
	{
		$url   = \trim($linkInfo);
		$title = '';
		$pos   = \strpos($url, ' ');
		if ($pos !== \false)
		{
			$title = \substr(\trim(\substr($url, $pos)), 1, -1);
			$url   = \substr($url, 0, $pos);
		}
		$tag->setAttribute($attrName, $this->text->decode($url));
		if ($title > '')
			$tag->setAttribute('title', $this->text->decode($title));
	}
	public function parse()
	{
		$pos = $this->text->indexOf('![');
		if ($pos === \false)
			return;
		if ($this->text->indexOf('](', $pos) !== \false)
			$this->parseInlineImages();
		if ($this->text->hasReferences)
			$this->parseReferenceImages();
	}
	protected function addImageTag($startPos, $endPos, $endLen, $linkInfo, $alt)
	{
		$tag = $this->parser->addTagPair('IMG', $startPos, 2, $endPos, $endLen);
		$this->setLinkAttributes($tag, $linkInfo, 'src');
		$tag->setAttribute('alt', $this->text->decode($alt));
		$this->text->overwrite($startPos, $endPos + $endLen - $startPos);
	}
	protected function parseInlineImages()
	{
		\preg_match_all(
			'/!\\[(?:[^\\x17[\\]]|\\[[^\\x17[\\]]*\\])*\\]\\(( *(?:[^\\x17\\s()]|\\([^\\x17\\s()]*\\))*(?=[ )]) *(?:"[^\\x17]*?"|\'[^\\x17]*?\'|\\([^\\x17)]*\\))? *)\\)/',
			$this->text,
			$matches,
			\PREG_OFFSET_CAPTURE | \PREG_SET_ORDER
		);
		foreach ($matches as $m)
		{
			$linkInfo = $m[1][0];
			$startPos = $m[0][1];
			$endLen   = 3 + \strlen($linkInfo);
			$endPos   = $startPos + \strlen($m[0][0]) - $endLen;
			$alt      = \substr($m[0][0], 2, \strlen($m[0][0]) - $endLen - 2);
			$this->addImageTag($startPos, $endPos, $endLen, $linkInfo, $alt);
		}
	}
	protected function parseReferenceImages()
	{
		\preg_match_all(
			'/!\\[((?:[^\\x17[\\]]|\\[[^\\x17[\\]]*\\])*)\\](?: ?\\[([^\\x17[\\]]+)\\])?/',
			$this->text,
			$matches,
			\PREG_OFFSET_CAPTURE | \PREG_SET_ORDER
		);
		foreach ($matches as $m)
		{
			$startPos = $m[0][1];
			$endPos   = $startPos + 2 + \strlen($m[1][0]);
			$endLen   = 1;
			$alt      = $m[1][0];
			$id       = $alt;
			if (isset($m[2][0], $this->text->linkReferences[$m[2][0]]))
			{
				$endLen = \strlen($m[0][0]) - \strlen($alt) - 2;
				$id        = $m[2][0];
			}
			elseif (!isset($this->text->linkReferences[$id]))
				continue;
			$this->addImageTag($startPos, $endPos, $endLen, $this->text->linkReferences[$id], $alt);
		}
	}
}