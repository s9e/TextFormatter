<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Parser,
    s9e\TextFormatter\PluginParser;

class WhitespaceParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();
		foreach ($matches as $m)
		{
			$tags[] = array(
				'name' => 'mark',
				'type' => Parser::SELF_CLOSING_TAG,
				'pos'  => $m[0][1],
				'len'  => strlen($m[0][0])
			);
		}

		return $tags;
	}
}