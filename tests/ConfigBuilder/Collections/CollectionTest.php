<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use Exception;
use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\Collection;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\Collection
*/
class CollectionTest extends Test
{
	/**
	* @group functional
	*/
	public function testCollectionIsCountable()
	{
		$dumbCollection = new DumbCollection(array('a' => 1, 'b' => 2, 'c' => 5));
		$this->assertSame(3, count($dumbCollection));
	}

	/**
	* @group functional
	*/
	public function testCollectionIsIterableWithForeach()
	{
		$expectedValue  = array('a' => 1, 'b' => 2, 'c' => 5);
		$dumbCollection = new DumbCollection($expectedValue);

		$actualValue = array();
		foreach ($dumbCollection as $k => $v)
		{
			$actualValue[$k] = $v;
		}

		$this->assertSame($expectedValue, $actualValue);
	}

	/**
	* @group functional
	* @testdox clear() empties the collection
	* @depends testCollectionIsCountable
	*/
	public function testClear()
	{
		$dumbCollection = new DumbCollection(array('a' => 1, 'b' => 2, 'c' => 5));
		$dumbCollection->clear();
		$this->assertSame(0, count($dumbCollection));
	}

	/**
	* @group functional
	* @testdox getConfig() returns the items as an array
	*/
	public function testGetConfig()
	{
		$dumbCollection = new DumbCollection(array('a' => 1, 'b' => 2, 'c' => 5));
		$this->assertEquals(
			array('a' => 1, 'b' => 2, 'c' => 5),
			$dumbCollection->getConfig()
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