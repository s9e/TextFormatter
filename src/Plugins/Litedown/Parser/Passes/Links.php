<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

use s9e\TextFormatter\Plugins\Litedown\Parser\LinkAttributesSetter;

class Links extends AbstractPass
{
	use LinkAttributesSetter;

	/**
	* {@inheritdoc}
	*/
	public function parse()
	{
		if ($this->text->indexOf('](') !== false)
		{
			$this->parseInlineLinks();
		}
		if ($this->text->indexOf('<') !== false)
		{
			$this->parseAutomaticLinks();
		}
		if ($this->text->hasReferences)
		{
			$this->parseReferenceLinks();
		}
	}

	/**
	* Add an image tag for given text span
	*
	* @param  integer $startPos Start tag position
	* @param  integer $endPos   End tag position
	* @param  integer $endLen   End tag length
	* @param  string  $linkInfo    URL optionally followed by space and a title
	* @return void
	*/
	protected function addLinkTag($startPos, $endPos, $endLen, $linkInfo)
	{
		// Give the link a slightly worse priority if this is a implicit reference and a slightly
		// better priority if it's an explicit reference or an inline link or to give it precedence
		// over possible BBCodes such as [b](https://en.wikipedia.org/wiki/B)
		$priority = ($endLen === 1) ? 1 : -1;

		$tag = $this->parser->addTagPair('URL', $startPos, 1, $endPos, $endLen, $priority);
		$this->setLinkAttributes($tag, $linkInfo, 'url');

		// Overwrite the markup without touching the link's text
		$this->text->overwrite($startPos, 1);
		$this->text->overwrite($endPos,   $endLen);
	}

	/**
	* Capture and return labels used in current text
	*
	* @return array Labels' text position as keys, lowercased text content as values
	*/
	protected function getLabels()
	{
		preg_match_all(
			'/\\[((?:[^\\x17[\\]]|\\[[^\\x17[\\]]*\\])*)\\]/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE
		);
		$labels = [];
		foreach ($matches[1] as $m)
		{
			$labels[$m[1] - 1] = strtolower($m[0]);
		}

		return $labels;
	}

	/**
	* Parse automatic links markup
	*
	* @return void
	*/
	protected function parseAutomaticLinks()
	{
		preg_match_all(
			'/<[-+.\\w]++([:@])[^\\x17\\s>]+?(?:>|\\x1B7)/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE
		);
		foreach ($matches[0] as $i => $m)
		{
			// Re-escape escape sequences in automatic links
			$content  = substr($this->text->decode(str_replace("\x1B", "\\\x1B", $m[0])), 1, -1);
			$startPos = $m[1];
			$endPos   = $startPos + strlen($m[0]) - 1;

			$tagName  = ($matches[1][$i][0] === ':') ? 'URL' : 'EMAIL';
			$attrName = strtolower($tagName);

			$this->parser->addTagPair($tagName, $startPos, 1, $endPos, 1)
			             ->setAttribute($attrName, $content);
		}
	}

	/**
	* Parse inline links markup
	*
	* @return void
	*/
	protected function parseInlineLinks()
	{
		preg_match_all(
			'/\\[(?:[^\\x17[\\]]|\\[[^\\x17[\\]]*\\])*\\]\\(( *(?:\\([^\\x17\\s()]*\\)|[^\\x17\\s)])*(?=[ )]) *(?:"[^\\x17]*?"|\'[^\\x17]*?\'|\\([^\\x17)]*\\))? *)\\)/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER
		);
		foreach ($matches as $m)
		{
			$linkInfo = $m[1][0];
			$startPos = $m[0][1];
			$endLen   = 3 + strlen($linkInfo);
			$endPos   = $startPos + strlen($m[0][0]) - $endLen;

			$this->addLinkTag($startPos, $endPos, $endLen, $linkInfo);
		}
	}

	/**
	* Parse reference links markup
	*
	* @return void
	*/
	protected function parseReferenceLinks()
	{
		$labels = $this->getLabels();
		foreach ($labels as $startPos => $id)
		{
			$labelPos = $startPos + 2 + strlen($id);
			$endPos   = $labelPos - 1;
			$endLen   = 1;

			if ($this->text->charAt($labelPos) === ' ')
			{
				++$labelPos;
			}
			if (isset($labels[$labelPos], $this->text->linkReferences[$labels[$labelPos]]))
			{
				$id     = $labels[$labelPos];
				$endLen = $labelPos + 2 + strlen($id) - $endPos;
			}
			if (isset($this->text->linkReferences[$id]))
			{
				$this->addLinkTag($startPos, $endPos, $endLen, $this->text->linkReferences[$id]);
			}
		}
	}
}