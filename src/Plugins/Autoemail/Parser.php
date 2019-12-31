<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autoemail;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$tagName  = $this->config['tagName'];
		$attrName = $this->config['attrName'];

		foreach ($matches as $m)
		{
			// Create a zero-width start tag right before the address
			$startTag = $this->parser->addStartTag($tagName, $m[0][1], 0);
			$startTag->setAttribute($attrName, $m[0][0]);

			// Create a zero-width end tag right after the address
			$endTag = $this->parser->addEndTag($tagName, $m[0][1] + strlen($m[0][0]), 0);

			// Pair the tags together
			$startTag->pairWith($endTag);
		}
	}
}