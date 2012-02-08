<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder;

use Exception,
    s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\Collection,
    s9e\TextFormatter\ConfigBuilder\ConfigurableItem;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder\ConfigurableItem
*/
class ConfigurableItemTest extends Test
{
	/**
	* @testdox setOption() invokes a Collection's clear() method before trying to set its content
	*/
	public function testSetOptionClearsCollection()
	{
		$collection = $this->getMock(
			's9e\\TextFormatter\\ConfigBuilder\\Collection',
			array('clear'),
			array('s9e\\TextFormatter\\ConfigBuilder\\ConfigurableItem')
		);

		$collection->expects($this->once())
		           ->method('clear');

		$item = new ConfigurableItemTestDummy;
		$item->collection = $collection;

		$item->setOption('collection', array());
	}

	/**
	* @testdox setOption() invokes a Collection's add() method for each element of the passed value
	*/
	public function testSetOptionAddsItemsToCollection()
	{
		$collection = $this->getMock(
			's9e\\TextFormatter\\ConfigBuilder\\Collection',
			array('add'),
			array(__NAMESPACE__ . '\\ConfigurableItemTestDummy')
		);

		$collection->expects($this->exactly(2))
		           ->method('add');

		$item = new ConfigurableItemTestDummy;
		$item->collection = $collection;

		$item->setOption('collection', array(
			'foo' => new ConfigurableItemTestDummy,
			'bar' => new ConfigurableItemTestDummy
		));
	}
}

class ConfigurableItemTestDummy extends ConfigurableItem
{
	static public function normalizeName($name)
	{
		return $name;
	}
}