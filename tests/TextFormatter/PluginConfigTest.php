<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\PluginConfig
*/
class PluginConfigTest extends Test
{
	public function testOverridesPropertiesWithValuesPassedInSecondParameter()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';

		$plugin = new CannedConfig($this->cb, array('foo' => 'bar'));

		$this->assertObjectHasAttribute('foo', $plugin);
	}
}