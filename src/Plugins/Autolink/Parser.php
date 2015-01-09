<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autolink;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	public function parse($text, array $matches)
	{
		$tagName  = $this->config['tagName'];
		$attrName = $this->config['attrName'];

		foreach ($matches as $m)
		{
			$url = $m[0][0];

			while (1)
			{
				$url = \preg_replace('#(?![-=/)])[>\\pP]+$#Du', '', $url);

				if (\substr($url, -1) === ')' && \substr_count($url, '(') < \substr_count($url, ')'))
				{
					$url = \substr($url, 0, -1);
					continue;
				}
				break;
			}

			$startTag = $this->parser->addStartTag($tagName, $m[0][1], 0);
			$startTag->setAttribute($attrName, $url);

			$endTag = $this->parser->addEndTag($tagName, $m[0][1] + \strlen($url), 0);

			$startTag->pairWith($endTag);
		}
	}
}