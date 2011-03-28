<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../src/TextFormatter/PluginConfig.php';
include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\PluginConfig
*/
class PluginConfigTest extends Test
{
	public function testOverridesPropertiesWithValuesPassedInSecondParameter()
	{
		$plugin = new MyConfig($this->cb, array('foo' => 'bar'));

		$this->assertObjectHasAttribute('foo', $plugin);
	}
}

class MyConfig extends PluginConfig
{
	public function getConfig()
	{
	}
}