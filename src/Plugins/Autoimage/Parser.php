<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autoimage;

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
			$this->parser->addTagPair($tagName, $m[0][1], 0, $m[0][1] + strlen($m[0][0]), 0, 2)
			             ->setAttribute($attrName, $m[0][0]);
		}
	}
}