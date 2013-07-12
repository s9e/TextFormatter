<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\StylesheetParameterCollection;
use s9e\TextFormatter\Configurator\Items\DynamicStylesheetParameter;
use s9e\TextFormatter\Configurator\Items\StaticStylesheetParameter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\StylesheetParameterCollection
*/
class StylesheetParameterCollectionTest extends Test
{
	/**
	* @testdox add() accepts instances of DynamicStylesheetParameter as-is
	*/
	public function testAddInstanceDynamic()
	{
		$parameter  = new DynamicStylesheetParameter;
		$collection = new StylesheetParameterCollection;

		$this->assertSame($parameter, $collection->add('foo', $parameter));
	}
	/**
	* @testdox add() accepts instances of StaticStylesheetParameter as-is
	*/
	public function testAddInstanceStatic()
	{
		$parameter  = new StaticStylesheetParameter;
		$collection = new StylesheetParameterCollection;

		$this->assertSame($parameter, $collection->add('foo', $parameter));
	}

	/**
	* @testdox add() normalizes values to an instance of StaticStylesheetParameter
	*/
	public function testAddString()
	{
		$parameter  = new StaticStylesheetParameter('bar');
		$collection = new StylesheetParameterCollection;

		$this->assertEquals($parameter, $collection->add('foo', 'bar'));
	}

	/**
	* @testdox add('foo') adds parameter 'foo' with an empty value
	*/
	public function testAddNoValue()
	{
		$collection = new StylesheetParameterCollection;
		$collection->add('foo');

		$this->assertEquals(new StaticStylesheetParameter, $collection->get('foo'));
	}

	/**
	* @testdox add('foo', 1) adds parameter 'foo' with value '1'
	*/
	public function testAdd()
	{
		$collection = new StylesheetParameterCollection;
		$collection->add('foo', 1);

		$this->assertEquals(new StaticStylesheetParameter('1'), $collection->get('foo'));
	}
}