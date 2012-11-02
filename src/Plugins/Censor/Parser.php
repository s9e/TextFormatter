<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;

use s9e\TextFormatter\Plugins\ParserBase;

class Censor extends ParserBase
{
	public function getTags($text, array $matches)
	{
		$tagName  = $this->config['tagName'];
		$attrName = $this->config['attrName'];

		$replacements = (isset($this->config['replacements']))
		              ? $this->config['replacements']
		              : array();

		foreach ($matches as $m)
		{
			$tag = $this->parser->addSelfClosingTag(array(
				'pos'  => $m[0][1],
				'name' => $tagName,
				'len'  => strlen($m[0][0])
			));

			foreach ($replacements as $mask => $replacement)
			{
				if (preg_match($mask, $m[0][0]))
				{
					$tag->setAttribute($attrName, $replacement);
					break;
				}
			}
		}
	}
}