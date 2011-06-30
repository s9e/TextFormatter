<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\PluginParser;

class LinebreakerParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();

		foreach ($matches as $m)
		{
			$tags[] = array(
				'pos'   => $m[0][1],
				'name'  => 'BR',
				'type'  => Parser::SELF_CLOSING_TAG,
				'len'   => strlen($m[0][0])
			);
		}

		return $tags;
	}
}