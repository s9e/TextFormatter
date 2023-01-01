<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autolink;

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
			// Linkify the trimmed URL
			$this->linkifyUrl($m[0][1], $this->trimUrl($m[0][0]));
		}
	}

	/**
	* Linkify given URL at given position
	*
	* @param  integer $tagPos URL's position in the text
	* @param  string  $url    URL
	* @return void
	*/
	protected function linkifyUrl($tagPos, $url)
	{
		// Create a zero-width end tag right after the URL
		$endPos = $tagPos + strlen($url);
		$endTag = $this->parser->addEndTag($this->config['tagName'], $endPos, 0);

		// If the URL starts with "www." we prepend "http://"
		if ($url[3] === '.')
		{
			$url = 'http://' . $url;
		}

		// Create a zero-width start tag right before the URL, with a slightly worse priority to
		// allow specialized plugins to use the URL instead
		$startTag = $this->parser->addStartTag($this->config['tagName'], $tagPos, 0, 1);
		$startTag->setAttribute($this->config['attrName'], $url);

		// Pair the tags together
		$startTag->pairWith($endTag);

		// Protect the tag's content from partial replacements with a low priority tag
		$contentTag = $this->parser->addVerbatim($tagPos, $endPos - $tagPos, 1000);
		$startTag->cascadeInvalidationTo($contentTag);
	}

	/**
	* Remove trailing punctuation from given URL
	*
	* We remove most ASCII non-letters and Unicode punctuation from the end of the string.
	* Exceptions:
	*  - dashes and underscores, (base64 IDs could end with one)
	*  - equal signs, (because of "foo?bar=")
	*  - plus signs, (used by some file share services to force download)
	*  - trailing slashes,
	*  - closing parentheses. (they are balanced separately)
	*
	* @param  string $url Original URL
	* @return string      Trimmed URL
	*/
	protected function trimUrl($url)
	{
		return preg_replace('#(?:(?![-=+)/_])[\\s!-.:-@[-`{-~\\pP])+$#Du', '', $url);
	}
}