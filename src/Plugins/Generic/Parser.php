<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Plugins\ParserBase;

class GenericParser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		foreach ($matches as $tagName => $regexpMatches)
		{
			foreach ($regexpMatches as $m)
			{
				$tag = $this->parser->addSelfClosingTag($tagName, $m[0][1], strlen($m[0][0]));

				foreach ($m as $k => $v)
				{
					if (!is_numeric($k))
					{
						$tag->addAttribute($k, $v[0]);
					}
				}
			}
		}
	}
}