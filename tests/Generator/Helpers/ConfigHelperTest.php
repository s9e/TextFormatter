<?php

namespace s9e\TextFormatter\Tests\Generator\Helpers;

use stdClass;
use s9e\TextFormatter\Generator\ConfigProvider;
use s9e\TextFormatter\Generator\Helpers\ConfigHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Generator\Helpers\ConfigHelper
*/
class ConfigHelperTest extends Test
{
	/**
	* @testdox toArray() works with deep arrays
	*/
	public function testDeepArrays()
	{
		$arr = array(
			'foo' => array('foo1' => 4, 'foo2' => 5),
			'bar' => array(1, 2, 3),
			'baz' => 42
		);

		$this->assertEquals($arr, ConfigHelper::toArray($arr));
	}

	/**
	* @testdox toArray() calls toConfig() for objects in deep arrays that implement ConfigProvider
	*/
	public function testConfigProviderDeep()
	{
		$arr = array(
			'foo' => array('foo1' => new ConfigProviderDummy),
			'bar' => new ConfigProviderDummy
		);

		$this->assertEquals(
			array(
				'foo' => array('foo1' => array('foo' => 42)),
				'bar' => array('foo' => 42)
			),
			ConfigHelper::toArray($arr)
		);
	}

	/**
	* @testdox toArray() throws an exception for objects in deep arrays that are not Traversable and do not implement ConfigProvider
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot convert object to array
	*/
	public function testInvalidObject()
	{
		ConfigHelper::toArray(array(new stdClass));
	}
}

class ConfigProviderDummy implements ConfigProvider
{
	public function toConfig()
	{
		return array('foo' => 42);
	}
}