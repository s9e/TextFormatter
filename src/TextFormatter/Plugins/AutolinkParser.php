<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
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

		foreach ($matches as $m)
		{
			$url = $m[0][0];

			/**
			* Remove trailing dots. We preserve right parentheses if there's the right number of
			* parentheses in the URL, as in http://en.wikipedia.org/wiki/Mars_(disambiguation) 
			*/
			do
			{
				$tmp = $url;
				$url = rtrim($url, '.');

				if (substr_count($url, '(') < substr_count($url, ')')
				 && substr($url, -1) === ')')
				{
					$url = substr($url, 0, -1);
				}
			}
			while ($tmp != $url);

			$tags[] = array(
				'pos'   => $m[0][1],
				'name'  => 'URL',
				'type'  => Parser::START_TAG,
				'len'   => 0,
				'attrs' => array('url' => $url)
			);

			$tags[] = array(
				'pos'   => $m[0][1] + strlen($url),
				'name'  => 'URL',
				'type'  => Parser::END_TAG,
				'len'   => 0
			);
		}

		return $tags;
	}
}