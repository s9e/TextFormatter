<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\PluginParser;

class AutolinkParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();

		$bbcode = $config['bbcode'];
		$param  = $config['param'];

		foreach ($matches as $m)
		{
			$url = $m[0][0];

			/**
			* Remove some trailing punctuation. We preserve right parentheses if there's a left
			* parenthesis in the URL, as in http://en.wikipedia.org/wiki/Mars_(disambiguation) 
			*/
			$url   = rtrim($url);
			$rtrim = (strpos($url, '(')) ? '.' : ').';
			$url   = rtrim($url, $rtrim);

			$tags[] = array(
				'pos'    => $m[0][1],
				'name'   => $bbcode,
				'type'   => Parser::START_TAG,
				'len'    => 0,
				'params' => array($param => $url)
			);
			$tags[] = array(
				'pos'    => $m[0][1] + strlen($url),
				'name'   => $bbcode,
				'type'   => Parser::END_TAG,
				'len'    => 0
			);
		}

		return $tags;
	}
}