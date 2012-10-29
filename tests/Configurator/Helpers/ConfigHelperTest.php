<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use stdClass;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\ConfigHelper
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
	* @testdox toArray() calls asConfig() for objects in deep arrays that implement ConfigProvider
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

	/**
	* @testdox Omits NULL values
	*/
	public function testNull()
	{
		$original = array('foo' => array(1), 'bar' => null);
		$expected = array('foo' => array(1));

		$this->assertSame($expected, ConfigHelper::toArray($original));
	}

	/**
	* @testdox Omits empty arrays from values
	*/
	public function testEmptyArray()
	{
		$original = array('foo' => array(1), 'bar' => array());
		$expected = array('foo' => array(1));

		$this->assertSame($expected, ConfigHelper::toArray($original));
	}

	/**
	* @testdox Omits empty Collections from values
	*/
	public function testEmptyCollection()
	{
		$original = array('foo' => 1, 'bar' => new Collection);
		$expected = array('foo' => 1);

		$this->assertSame($expected, ConfigHelper::toArray($original));
	}
}

class ConfigProviderDummy implements ConfigProvider
{
	public function asConfig()
	{
		return array('foo' => 42);
	}
}