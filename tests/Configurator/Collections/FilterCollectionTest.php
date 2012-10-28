<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\FilterCollection;
use s9e\TextFormatter\Configurator\Items\CallbackTemplate;
use s9e\TextFormatter\Configurator\Items\Filter;

/**
* @covers s9e\TextFormatter\Configurator\Collections\FilterCollection
*/
class FilterCollectionTest extends Test
{
	/**
	* @testdox Throws an exception if the value is not an instance of Filter
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Not an instance of s9e\TextFormatter\Configurator\Items\Filter
	*/
	public function testInvalidValue()
	{
		$collection = new FilterCollection;
		$collection->add('foo', 'bar');
	}

	/**
	* @testdox Throws an exception if the filter's callback is a built-in filter
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Custom filters' callback must be an instance of CallbackTemplate
	*/
	public function testInvalidCallback()
	{
		$collection = new FilterCollection;
		$filter     = new Filter('#foo');

		$collection->add('foo', $filter);
	}

	/**
	* @testdox Accepts instances of Filter
	*/
	public function testValid()
	{
		$collection = new FilterCollection;
		$callback   = new CallbackTemplate('mt_rand');
		$filter     = new Filter($callback);

		$this->assertSame(
			$filter,
			$collection->add('foo', $filter)
		);
	}
}