<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\PluginParser;

class HTMLElementsParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();

		foreach ($matches as $m)
		{
			$tagType = ($text[$m[0][1] + 1] === '/')
			         ? Parser::END_TAG
			         : Parser::START_TAG;

			$tagName = $this->config['prefix']
			         . ':'
			         . strtolower($m[1 + ($tagType === Parser::START_TAG)][0]);

			$attrs = array();

			if ($tagType === Parser::START_TAG)
			{
				if (substr($m[0][0], -2) === '/>')
				{
					$tagType = Parser::SELF_CLOSING_TAG;
				}

				/**
				* Capture attributes
				*/
				preg_match_all(
					$this->config['attrRegexp'],
					$m[3][0],
					$attrMatches,
					PREG_SET_ORDER
				);

				foreach ($attrMatches as $attrMatch)
				{
					$pos = strpos($attrMatch[0], '=');

					/**
					* Give boolean attributes a value equal to their name, lowercased
					*/
					if ($pos === false)
					{
						$pos = strlen($attrMatch[0]);
						$attrMatch[0] .= '=' . strtolower($attrMatch[0]);
					}

					$attrName  = strtolower(trim(substr($attrMatch[0], 0, $pos)));
					$attrValue = trim(substr($attrMatch[0], 1 + $pos));

					if ($attrValue[0] === '"'
					 || $attrValue[0] === "'")
					{
						 $attrValue = substr($attrValue, 1, -1);
					}

					// NOTE: html_entity_decode()'s translation table is far from exhaustive
					$attrs[$attrName] = html_entity_decode($attrValue, ENT_QUOTES, 'UTF-8');
				}
			}

			$tags[] = array(
				'pos'   => $m[0][1],
				'len'   => strlen($m[0][0]),
				'name'  => $tagName,
				'type'  => $tagType,
				'attrs' => $attrs
			);
		}

		return $tags;
	}
}