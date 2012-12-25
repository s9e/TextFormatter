<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\FilterCollection;
use s9e\TextFormatter\Configurator\Items\CallbackPlaceholder;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;

/**
* @covers s9e\TextFormatter\Configurator\Collections\FilterCollection
*/
class FilterCollectionTest extends Test
{
	/**
	* @testdox Throws an exception if the value is not an instance of ProgrammableCallback
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Not an instance of s9e\TextFormatter\Configurator\Items\ProgrammableCallback
	*/
	public function testInvalidValue()
	{
		$collection = new FilterCollection;
		$collection->add('foo', 'bar');
	}

	/**
	* @testdox Accepts instances of Filter
	*/
	public function testValid()
	{
		$collection = new FilterCollection;
		$filter     = new ProgrammableCallback('mt_rand');

		$this->assertSame(
			$filter,
			$collection->add('foo', $filter)
		);
	}
}