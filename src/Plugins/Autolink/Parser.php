<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Plugins\ParserBase;

class AutolinkParser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
		{
			$url      = $m[0][0];
			$startPos = $m[0][1];
			$endPos   = $startPos + strlen($url);

			// Remove trailing punctuation. We preserve right parentheses if there's a balanced
			// number of parentheses in the URL, e.g.
			//   http://en.wikipedia.org/wiki/Mars_(disambiguation) 
			while (1)
			{
				$url = preg_replace('#(?![\\)=\\-/])\\pP+$#Du', '', $url);

				if (substr($url, -1) === ')'
				 && substr_count($url, '(') < substr_count($url, ')'))
				{
					$url = substr($url, 0, -1);
					continue;
				}
				break;
			}

			$this->parser->addStartTag($this->config['tagName'], $startPos, 0)->setAttribute($this->config['attrName'], $url);
			$this->parser->addEndTag($this->config['tagName'], $endPos, 0);

			/**
			* @todo pair tags together
			*/
		}
	}
}