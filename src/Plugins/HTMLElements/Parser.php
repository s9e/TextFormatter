<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLElements;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritDoc}
	*/
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
		{
			// Test whether this is an end tag
			$isEnd = (bool) ($text[$m[0][1] + 1] === '/');

			$pos = $m[0][1];
			$len = strlen($m[0][0]);
			$tagName = $this->config['prefix'] . ':' . strtolower($m[2 - $isEnd][0]);

			if ($isEnd)
			{
				$this->parser->addEndTag($tagName, $pos, $len);
				continue;
			}

			// Test whether it's a self-closing tag or a start tag
			$tag = (substr($m[0][0], -2) === '/>')
			     ? $this->parser->addSelfClosingTag($tagName, $pos, $len)
			     : $this->parser->addStartTag($tagName, $pos, $len);

			// Capture attributes
			preg_match_all(
				'/[a-z][-a-z]*(?:\\s*=\\s*(?:"[^"]*"|\'[^\']*\'|[^\\s"\'=<>`]+))?/i',
				$m[3][0],
				$attrMatches,
				PREG_SET_ORDER
			);

			foreach ($attrMatches as $attrMatch)
			{
				$pos = strpos($attrMatch[0], '=');

				/**
				* If there's no equal sign, it's a boolean attribute and we generate a value equal
				* to the attribute's name, lowercased
				*
				* @link http://www.w3.org/html/wg/drafts/html/master/single-page.html#boolean-attributes
				*/
				if ($pos === false)
				{
					$pos = strlen($attrMatch[0]);
					$attrMatch[0] .= '=' . strtolower($attrMatch[0]);
				}

				// Normalize the attribute name, remove the whitespace around its value to account
				// for cases like <b title = "foo"/>
				$attrName  = strtolower(trim(substr($attrMatch[0], 0, $pos)));
				$attrValue = trim(substr($attrMatch[0], 1 + $pos));

				// Remove quotes around the value
				if ($attrValue[0] === '"'
				 || $attrValue[0] === "'")
				{
					 $attrValue = substr($attrValue, 1, -1);
				}

				$tag->setAttribute($attrName, html_entity_decode($attrValue, ENT_QUOTES, 'UTF-8'));
			}
		}
	}
}