<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLElements;

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
			// Test whether this is an end tag
			$isEnd = (bool) ($text[$m[0][1] + 1] === '/');

			$pos    = $m[0][1];
			$len    = strlen($m[0][0]);
			$elName = strtolower($m[2 - $isEnd][0]);

			// Use the element's alias if applicable, or the  name of the element (with the
			// configured prefix) otherwise
			$tagName = (isset($this->config['aliases'][$elName]['']))
			         ? $this->config['aliases'][$elName]['']
			         : $this->config['prefix'] . ':' . $elName;

			if ($isEnd)
			{
				$this->parser->addEndTag($tagName, $pos, $len);
				continue;
			}

			// Test whether it's a self-closing tag or a start tag.
			//
			// A self-closing tag will become one start tag consuming all of the text followed by a
			// 0-width end tag. Alternatively, it could be replaced by a pair of 0-width tags plus
			// an ignore tag to prevent the text in between from being output
			$tag = (preg_match('/(<\\S+|[\'"\\s])\\/>$/', $m[0][0]))
			     ? $this->parser->addTagPair($tagName, $pos, $len, $pos + $len, 0)
			     : $this->parser->addStartTag($tagName, $pos, $len);

			$this->captureAttributes($tag, $elName, $m[3][0]);
		}
	}

	/**
	* Capture all attributes in given string
	*
	* @param  Tag    $tag    Target tag
	* @param  string $elName Name of the HTML element
	* @param  string $str    String containing the attribute declarations
	* @return void
	*/
	protected function captureAttributes(Tag $tag, $elName, $str)
	{
		preg_match_all(
			'/[a-z][-a-z0-9]*(?>\\s*=\\s*(?>"[^"]*"|\'[^\']*\'|[^\\s"\'=<>`]+))?/i',
			$str,
			$attrMatches
		);

		foreach ($attrMatches[0] as $attrMatch)
		{
			$pos = strpos($attrMatch, '=');

			/**
			* If there's no equal sign, it's a boolean attribute and we generate a value equal
			* to the attribute's name, lowercased
			*
			* @link http://www.w3.org/html/wg/drafts/html/master/single-page.html#boolean-attributes
			*/
			if ($pos === false)
			{
				$pos = strlen($attrMatch);
				$attrMatch .= '=' . strtolower($attrMatch);
			}

			// Normalize the attribute name, remove the whitespace around its value to account
			// for cases like <b title = "foo"/>
			$attrName  = strtolower(trim(substr($attrMatch, 0, $pos)));
			$attrValue = trim(substr($attrMatch, 1 + $pos));

			// Use the attribute's alias if applicable
			if (isset($this->config['aliases'][$elName][$attrName]))
			{
				$attrName = $this->config['aliases'][$elName][$attrName];
			}

			// Remove quotes around the value
			if ($attrValue[0] === '"' || $attrValue[0] === "'")
			{
				$attrValue = substr($attrValue, 1, -1);
			}

			$tag->setAttribute($attrName, html_entity_decode($attrValue, ENT_QUOTES, 'UTF-8'));
		}
	}
}