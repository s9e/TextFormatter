<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use Exception;
use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\Collection;

/**
* @covers s9e\TextFormatter\Configurator\Collections\Collection
*/
class CollectionTest extends Test
{
	public function testCollectionIsCountable()
	{
		$collection = new DumbCollection(['a' => 1, 'b' => 2, 'c' => 5]);
		$this->assertSame(3, count($collection));
	}

	public function testCollectionIsIterableWithForeach()
	{
		$expectedValue = ['a' => 1, 'b' => 2, 'c' => 5];
		$collection    = new DumbCollection($expectedValue);

		$actualValue = [];
		foreach ($collection as $k => $v)
		{
			$actualValue[$k] = $v;
		}

		$this->assertSame($expectedValue, $actualValue);
	}

	/**
	* @testdox clear() empties the collection
	* @depends testCollectionIsCountable
	*/
	public function testClear()
	{
		$collection = new DumbCollection(['a' => 1, 'b' => 2, 'c' => 5]);
		$collection->clear();
		$this->assertSame(0, count($collection));
	}

	/**
	* @testdox asConfig() returns the items as an array
	*/
	public function testGetConfig()
	{
		$collection = new DumbCollection(['a' => 1, 'b' => 2, 'c' => 5]);
		$this->assertEquals(
			['a' => 1, 'b' => 2, 'c' => 5],
			$collection->asConfig()
		);
	}
}

class DumbCollection extends Collection
{
	public function __construct(array $items)
	{
		$this->items = $items;
	}
}