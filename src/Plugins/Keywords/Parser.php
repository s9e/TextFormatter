<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
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

		$onlyFirst = !empty($this->config['onlyFirst']);
		$keywords  = [];

		foreach ($regexps as $regexp)
		{
			preg_match_all($regexp, $text, $matches, PREG_OFFSET_CAPTURE);

			foreach ($matches[0] as list($value, $pos))
			{
				if ($onlyFirst)
				{
					if (isset($keywords[$value]))
					{
						continue;
					}

					$keywords[$value] = 1;
				}

				$this->parser->addSelfClosingTag($tagName, $pos, strlen($value))
				             ->setAttribute($attrName, $value);
			}
		}
	}
}