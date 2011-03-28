<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\Plugins\BBCodesConfig;

include_once __DIR__ . '/../Test.php';

class AutoloadersTest extends Test
{
	/**
	* @runInSeparateProcess
	*/
	public function testConfigBuilderLoadsCorePluginsFiles()
	{
		$this->assertTrue($this->cb->loadPlugin('BBCodes') instanceof BBCodesConfig);
	}

	/**
	* @runInSeparateProcess
	*/
	public function testParserLoadsPluginFiles()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->parser->parse('[X/]');
	}
}