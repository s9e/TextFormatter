<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript;

use s9e\TextFormatter\Configurator\JavaScript\ConfigValue;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Encoder;
use s9e\TextFormatter\Tests\Test;
use stdClass;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\ConfigValue
*/
class ConfigValueTest extends Test
{
	/**
	* @testdox getUseCount() returns 0 by default
	*/
	public function testGetUseCountalue()
	{
		$configValue = new ConfigValue([], 'foo');

		$this->assertSame(0, $configValue->getUseCount());
	}

	/**
	* @testdox getValue() returns the original value
	*/
	public function testGetValue()
	{
		$obj   = new stdClass;
		$configValue = new ConfigValue($obj, 'foo');

		$this->assertSame($obj, $configValue->getValue());
	}

	/**
	* @testdox getVarName() returns the assigned variable name
	*/
	public function testGetVarName()
	{
		$configValue = new ConfigValue([], 'foo');

		$this->assertSame('foo', $configValue->getVarName());
	}

	/**
	* @testdox incrementUseCount() increments the use count by 1
	*/
	public function testIncrementUseCount()
	{
		$configValue = new ConfigValue([], 'foo');
		$configValue->incrementUseCount();
		$this->assertSame(1, $configValue->getUseCount());
		$configValue->incrementUseCount();
		$this->assertSame(2, $configValue->getUseCount());
	}

	/**
	* @testdox deduplicate() doesn't do anything if the use count is 1
	*/
	public function testDeduplicateFalse()
	{
		$configValue = new ConfigValue([], 'foo');
		$configValue->incrementUseCount();
		$configValue->deduplicate();
		$this->assertFalse($configValue->isDeduplicated());
	}

	/**
	* @testdox deduplicate() marks the value as deduplicated if the use count is 2
	*/
	public function testDeduplicateTrue()
	{
		$configValue = new ConfigValue([], 'foo');
		$configValue->incrementUseCount();
		$configValue->incrementUseCount();
		$configValue->deduplicate();
		$this->assertTrue($configValue->isDeduplicated());
	}

	/**
	* @testdox deduplicate() sets the use counter of the instance to 1
	*/
	public function testDeduplicateCounter()
	{
		$configValue = new ConfigValue([0, 0], 'foo');
		$configValue->incrementUseCount();
		$configValue->incrementUseCount();
		$configValue->deduplicate();

		$this->assertSame(1, $configValue->getUseCount());
	}

	/**
	* @testdox deduplicate() decrements the use counter of config values contained in the instance
	*/
	public function testDeduplicateCascade1()
	{
		$sub = new ConfigValue([0, 0], 'sub');
		$sub->incrementUseCount();
		$sub->incrementUseCount();

		$configValue = new ConfigValue([$sub], 'foo');
		$configValue->incrementUseCount();
		$configValue->incrementUseCount();
		$configValue->deduplicate();

		$this->assertSame(1, $sub->getUseCount());
	}

	/**
	* @testdox deduplicate() decrements the use counter of config values contained in the instance
	*/
	public function testDeduplicateCascade2()
	{
		$sub = new ConfigValue([0, 0], 'sub');
		$sub->incrementUseCount();
		$sub->incrementUseCount();
		$sub->incrementUseCount();
		$sub->incrementUseCount();

		$configValue = new ConfigValue([$sub, $sub], 'foo');
		$configValue->incrementUseCount();
		$configValue->incrementUseCount();
		$configValue->deduplicate();

		$this->assertSame(2, $sub->getUseCount());
	}
}