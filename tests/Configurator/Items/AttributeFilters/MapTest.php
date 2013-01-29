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
		       ->with(['one' => 'uno', 'two' => 'dos'], true, true);

		$filter->__construct(['one' => 'uno', 'two' => 'dos'], true, true);
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
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$filter = new Map(['foo' => 'bar']);
		$this->assertInternalType('array', $filter->asConfig());
	}

	/**
	* @testdox setMap() creates case-insensitive regexps by default
	*/
	public function testSetMapCaseInsensitive()
	{
		$filter = new Map;
		$filter->setMap(['foo' => 'bar']);

		$vars = $filter->getVars();

		$this->assertSame(
			'/^foo$/Di',
			(string) $vars['map'][0][0]
		);
	}

	/**
	* @testdox setMap() creates case-sensitive regexps if its second argument is TRUE
	*/
	public function testSetMapCaseSensitive()
	{
		$filter = new Map;
		$filter->setMap(['foo' => 'bar'], true);

		$vars = $filter->getVars();

		$this->assertSame(
			'/^foo$/D',
			(string) $vars['map'][0][0]
		);
	}

	/**
	* @testdox setMap() appends a catch-all regexp that maps to FALSE if its third argument is TRUE
	*/
	public function testSetMapStrict()
	{
		$filter = new Map;
		$filter->setMap(['foo' => 'bar'], false, true);

		$vars = $filter->getVars();

		$this->assertSame(
			'//',
			(string) $vars['map'][1][0]
		);
		$this->assertFalse($vars['map'][1][1]);
	}

	/**
	* @testdox setMap() uses the pattern modifier 'u' if a regexp is not entirely ASCII
	*/
	public function testSetMapUnicode()
	{
		$filter = new Map;
		$filter->setMap(['pokémon' => 'yugioh']);

		$vars = $filter->getVars();

		$this->assertSame(
			'/^pokémon$/Diu',
			(string) $vars['map'][0][0]
		);
	}

	/**
	* @testdox setMap() throws an exception if the second argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be a boolean
	*/
	public function testSetMapNotBool2()
	{
		$filter = new Map;
		$filter->setMap(['foo' => 'bar'], 'notbool');
	}

	/**
	* @testdox setMap() throws an exception if the third argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be a boolean
	*/
	public function testSetMapNotBool3()
	{
		$filter = new Map;
		$filter->setMap(['foo' => 'bar'], true, 'notbool');
	}
}