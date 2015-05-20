<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Autolink;

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
		$chars    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

		foreach ($matches as $m)
		{
			$url    = $m[0][0];
			$tagPos = $m[0][1];

			// Make sure that the URL is not preceded by an alphanumeric character
			if ($tagPos > 0 && strpos($chars, $text[$tagPos - 1]) !== false)
			{
				continue;
			}

			// Remove trailing punctuation and right angle brackets. We preserve right parentheses
			// if there's a balanced number of parentheses in the URL, e.g.
			//   http://en.wikipedia.org/wiki/Mars_(disambiguation)
			while (1)
			{
				// We remove all Unicode punctuation except dashes (some YouTube URLs end with a
				// dash due to the video ID), equal signs (because of "foo?bar="), trailing slashes,
				// and parentheses, which are balanced separately
				$url = preg_replace('#(?![-=/)])[>\\pP]+$#Du', '', $url);

				if (substr($url, -1) === ')' && substr_count($url, '(') < substr_count($url, ')'))
				{
					$url = substr($url, 0, -1);
					continue;
				}
				break;
			}

			// Create a zero-width end tag right after the URL
			$endTag = $this->parser->addEndTag($tagName, $tagPos + strlen($url), 0);

			// If the URL starts with "www." we prepend "http://"
			if ($url[3] === '.')
			{
				$url = 'http://' . $url;
			}

			// Create a zero-width start tag right before the URL
			$startTag = $this->parser->addStartTag($tagName, $tagPos, 0);
			$startTag->setAttribute($attrName, $url);

			// Pair the tags together
			$startTag->pairWith($endTag);
		}
	}
}