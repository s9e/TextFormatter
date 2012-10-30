<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\CallbackTemplate;
use s9e\TextFormatter\Configurator\Items\Filter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\Filter
*/
class FilterTest extends Test
{
	/**
	* @testdox __construct() accepts an instance of CallbackTemplate as its first argument
	*/
	public function testCallbackTemplate()
	{
		$callback = new CallbackTemplate('strtolower');
		$filter   = new Filter($callback);

		$this->assertSame($callback, $filter->getCallback());
	}

	/**
	* @testdox __construct() accepts the name of a built-in filter such as '#url' as its first argument
	*/
	public function testBuiltinFilter()
	{
		$callback = '#url';
		$filter   = new Filter($callback);

		$this->assertSame($callback, $filter->getCallback());
	}

	/**
	* @testdox __construct() throws an exception on invalid filters
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Argument 1 passed to Filter::__construct() must be a CallbackTemplate instance or the name of a built-in filter
	*/
	public function testInvalidFilter()
	{
		new Filter('*invalid*');
	}

	/**
	* @testdox Filter vars can be passed as __construct()'s second argument
	*/
	public function testConstructorVars()
	{
		$vars   = array('foo' => 'bar');
		$filter = new Filter('#url', $vars);

		$this->assertSame($vars, $filter->getVars());
	}

	/**
	* @testdox Filter vars can be set with setVars()
	*/
	public function testSetVars()
	{
		$vars   = array('foo' => 'bar');
		$filter = new Filter('#url', array('bar' => 'baz'));

		$filter->setVars($vars);

		$this->assertSame($vars, $filter->getVars());
	}

	/**
	* @testdox asConfig() returns the filter's callback and vars in an array
	*/
	public function testAsConfig()
	{
		$filter = new Filter('#url', array('bar' => 'baz'));

		$this->assertEquals(
			array('callback' => '#url', 'vars' => array('bar' => 'baz')),
			$filter->asConfig()
		);
	}
}