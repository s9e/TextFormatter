<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Map;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Map
*/
class MapTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterMap()
	*/
	public function testCallback()
	{
		$filter = new Map;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterMap',
			$filter->getCallback()
		);
	}

	/**
	* @testdox __construct() forwards its arguments to setMap()
	*/
	public function testConstructorArguments()
	{
		$className = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\Map';
		$filter = $this->getMockBuilder($className)
		               ->disableOriginalConstructor()
		               ->getMock();

		$filter->expects($this->once())
		       ->method('setMap')
		       ->with(array('one' => 'uno', 'two' => 'dos'), true, true);

		$filter->__construct(array('one' => 'uno', 'two' => 'dos'), true, true);
	}

	/**
	* @testdox asConfig() throws an exception if the 'map' var is missing
	* @expectedException RuntimeException
	* @expectedExceptionMessage Map filter is missing a 'map' value
	*/
	public function testMissingMap()
	{
		$filter = new Map;
		$filter->asConfig();
	}

	/**
	* @testdox setMap() throws an exception if the second argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be a boolean
	*/
	public function testSetMapNotBool2()
	{
		$filter = new Map;
		$filter->setMap(array('foo' => 'bar'), 'notbool');
	}

	/**
	* @testdox setMap() throws an exception if the third argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be a boolean
	*/
	public function testSetMapNotBool3()
	{
		$filter = new Map;
		$filter->setMap(array('foo' => 'bar'), true, 'notbool');
	}
}