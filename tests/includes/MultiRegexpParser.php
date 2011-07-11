<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Parser,
    s9e\TextFormatter\PluginParser;

class MultiRegexpParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();
		foreach ($matches as $k => $_matches)
		{
			foreach ($_matches as $m)
			{
				$tags[] = array(
					'name' => 'X',
					'type' => Parser::SELF_CLOSING_TAG,
					'pos'  => $m[0][1],
					'len'  => 1
				);
			}
		}

		return $tags;
	}
}