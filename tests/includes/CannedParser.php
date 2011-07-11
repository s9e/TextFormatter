<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\PluginParser;

class CannedParser extends PluginParser
{
	public $_calledCount = 0;

	public function setUp()
	{
		++$this->_calledCount;
	}

	public function getTags($text, array $matches)
	{
		return $this->config['tags'];
	}
}