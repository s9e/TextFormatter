<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Hashmap;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Hashmap
*/
class HashmapTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterHashmap()
	*/
	public function testCallback()
	{
		$filter = new Hashmap;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterHashmap',
			$filter->getCallback()
		);
	}

	/**
	* @testdox __construct() forwards its arguments to setMap()
	*/
	public function testConstructorArguments()
	{
		$className = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\Hashmap';
		$filter = $this->getMockBuilder($className)
		               ->disableOriginalConstructor()
		               ->getMock();

		$filter->expects($this->once())
		       ->method('setMap')
		       ->with(['one' => 'uno', 'two' => 'dos'], true);

		$filter->__construct(['one' => 'uno', 'two' => 'dos'], true);
	}

	/**
	* @testdox asConfig() throws an exception if the 'map' var is missing
	* @expectedException RuntimeException
	* @expectedExceptionMessage Hashmap filter is missing a 'map' value
	*/
	public function testMissingHashmap()
	{
		$filter = new Hashmap;
		$filter->asConfig();
	}

	/**
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$filter = new Hashmap(['foo' => 'bar']);
		$this->assertInternalType('array', $filter->asConfig());
	}

	/**
	* @testdox Creates a sparse map by default
	*/
	public function testSetHashmapStrict()
	{
		$filter = new Hashmap(['foo' => 'bar']);

		$vars = $filter->getVars();

		$this->assertFalse($vars['strict']);
	}

	/**
	* @testdox Creates a strict map if the second argument is TRUE
	*/
	public function testSetHashmapSparse()
	{
		$filter = new Hashmap(['foo' => 'bar'], true);

		$vars = $filter->getVars();

		$this->assertTrue($vars['strict']);
	}

	/**
	* @testdox Throws an exception if the second argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be a boolean
	*/
	public function testStrictNotBool()
	{
		new Hashmap(['foo' => 'bar'], 'notbool');
	}

	/**
	* @testdox Values identical to their key are optimized away if the map is sparse
	*/
	public function testOptimizeSparse()
	{
		$filter = new Hashmap(['foo' => 'bar', 'bar' => 'bar']);

		$vars = $filter->getVars();

		$this->assertSame(['foo' => 'bar'], $vars['map']);
	}

	/**
	* @testdox Values identical to their key are preserved if the map is strict
	*/
	public function testNotOptimizeStrict()
	{
		$filter = new Hashmap(['foo' => 'bar', 'bar' => 'bar', 'baz' => 'quux'], true);

		$vars = $filter->getVars();

		$this->assertEquals(['foo' => 'bar', 'bar' => 'bar', 'baz' => 'quux'], $vars['map']);
	}
}