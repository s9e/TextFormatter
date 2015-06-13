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
		$chars    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		foreach ($matches as $m)
		{
			$url    = $m[0][0];
			$tagPos = $m[0][1];
			if ($tagPos > 0 && \strpos($chars, $text[$tagPos - 1]) !== \false)
				continue;
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
			$endTag = $this->parser->addEndTag($tagName, $tagPos + \strlen($url), 0);
			if ($url[3] === '.')
				$url = 'http://' . $url;
			$startTag = $this->parser->addStartTag($tagName, $tagPos, 0);
			$startTag->setAttribute($attrName, $url);
			$startTag->pairWith($endTag);
		}
	}
}