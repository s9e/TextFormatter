<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use Exception,
    s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\Collections\Collection;

include_once __DIR__ . '/../../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\Collection
*/
class CollectionTest extends Test
{
	public function testCollectionIsCountable()
	{
		$dumbCollection = new DumbCollection(array('a' => 1, 'b' => 2, 'c' => 5));
		$this->assertSame(3, count($dumbCollection));
	}

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
	* @testdox clear() empties the collection
	* @depends testCollectionIsCountable
	*/
	public function testClear()
	{
		$dumbCollection = new DumbCollection(array('a' => 1, 'b' => 2, 'c' => 5));
		$dumbCollection->clear();
		$this->assertSame(0, count($dumbCollection));
	}
}

class DumbCollection extends Collection
{
	public function __construct(array $items)
	{
		$this->items = $items;
	}
}