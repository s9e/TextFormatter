<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\FilterCollection;
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
	public function testInvalid()
	{
		$collection = new FilterCollection;
		$collection->add('foo', 'bar');
	}
}