<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\TextFormatter\PluginParser;

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