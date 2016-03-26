<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
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
		foreach ($matches as $m)
		{
			// Linkify the trimmed URL
			$this->linkifyUrl($m[0][1], $this->trimUrl($m[0][0]));
		}
	}

	/**
	* Linkify given URL at given position
	*
	* @param  integer $tagPos URL's position in the text
	* @param  string  $url    URL
	* @return void
	*/
	protected function linkifyUrl($tagPos, $url)
	{
		// Ensure that the anchor (scheme/www) is still there
		if (!preg_match('/^[^:]+:|^www\\./i', $url))
		{
			return;
		}

		// Create a zero-width end tag right after the URL
		$endTag = $this->parser->addEndTag($this->config['tagName'], $tagPos + strlen($url), 0);

		// If the URL starts with "www." we prepend "http://"
		if ($url[3] === '.')
		{
			$url = 'http://' . $url;
		}

		// Create a zero-width start tag right before the URL
		$startTag = $this->parser->addStartTag($this->config['tagName'], $tagPos, 0);
		$startTag->setAttribute($this->config['attrName'], $url);

		// Give this tag a slightly lower priority than default to allow specialized plugins
		// to use the URL instead
		$startTag->setSortPriority(1);

		// Pair the tags together
		$startTag->pairWith($endTag);
	}

	/**
	* Remove trailing punctuation from given URL
	*
	* Removes trailing punctuation and right angle brackets. We preserve right parentheses
	* if there's a balanced number of parentheses in the URL, e.g.
	*   http://en.wikipedia.org/wiki/Mars_(disambiguation)
	*
	* @param  string $url Original URL
	* @return string      Trimmed URL
	*/
	protected function trimUrl($url)
	{
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

		return $url;
	}
}