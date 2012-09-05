<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\PluginParser;

class GenericParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();

		foreach ($matches as $tagName => $regexpMatches)
		{
			foreach ($regexpMatches as $m)
			{
				$attrs = array();

				foreach ($m as $k => $v)
				{
					if (!is_numeric($k))
					{
						$attrs[$k] = $v[0];
					}
				}

				$tags[] = array(
					'pos'   => $m[0][1],
					'name'  => $tagName,
					'type'  => Parser::SELF_CLOSING_TAG,
					'len'   => strlen($m[0][0]),
					'attrs' => $attrs
				);
			}
		}

		return $tags;
	}
}