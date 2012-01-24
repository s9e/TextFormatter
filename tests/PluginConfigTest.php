<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\PluginConfig;

include_once __DIR__ . '/../src/autoloader.php';

/**
* @covers s9e\TextFormatter\PluginConfig
*/
class PluginConfigTest extends Test
{
	public function testOverridesPropertiesWithValuesPassedInSecondParameter()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';

		$plugin = new CannedConfig($this->cb, array('foo' => 'bar'));

		$this->assertObjectHasAttribute('foo', $plugin);
	}

	/**
	* @test
	* @testdox getJSConfig() forwards the result of getConfig() by default
	*/
	public function getJSConfig_forwards_the_result_of_getConfig_by_default()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';

		$plugin = new CannedConfig($this->cb);

		$this->assertSame(
			$plugin->getConfig(),
			$plugin->getJSConfig()
		);
	}

	/**
	* @test
	* @testdox getJSConfigMeta() returns an empty array by default
	*/
	public function getJSConfigMeta_returns_an_empty_array_by_default()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';

		$plugin = new CannedConfig($this->cb);

		$this->assertSame(
			array(),
			$plugin->getJSConfigMeta()
		);
	}

	/**
	* @test
	* @testdox getJSParser() returns false by default
	*/
	public function getJSParser_returns_false_by_default()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';

		$plugin = new CannedConfig($this->cb);

		$this->assertFalse($plugin->getJSParser());
	}
}