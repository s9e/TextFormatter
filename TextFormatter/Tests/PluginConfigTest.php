<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../PluginConfig.php';
include_once __DIR__ . '/Test.php';

class MyConfig extends PluginConfig
{
	public function getConfig()
	{
	}
}

/**
* @covers s9e\Toolkit\TextFormatter\PluginConfig
*/
class PluginConfigTest extends Test
{
	public function testOverridesPropertiesWithValuesPassedInSecondParameter()
	{
		$cb = new ConfigBuilder;
		$plugin = new MyConfig($cb, array('foo' => 'bar'));

		$this->assertObjectHasAttribute('foo', $plugin);
	}
}