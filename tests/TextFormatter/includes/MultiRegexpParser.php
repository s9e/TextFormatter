<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\PluginParser;

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