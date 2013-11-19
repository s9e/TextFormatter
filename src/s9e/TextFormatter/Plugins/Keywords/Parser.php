<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Keywords;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$regexps  = $this->config['regexps'];
		$tagName  = $this->config['tagName'];
		$attrName = $this->config['attrName'];

		foreach ($regexps as $regexp)
		{
			preg_match_all($regexp, $text, $matches, PREG_OFFSET_CAPTURE);

			foreach ($matches[0] as list($value, $pos))
			{
				$len = strlen($value);

				if (isset($this->config['map'][$value]))
				{
					$value = $this->config['map'][$value];
				}

				$this->parser->addSelfClosingTag($tagName, $pos, $len)
				             ->setAttribute($attrName, $value);
			}
		}
	}
}