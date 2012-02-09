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
	protected function getMockItem()
	{
		return call_user_func(
			array($this, 'getMock'),
			__NAMESPACE__ . '\\ConfigurableItemTestDummy',
			func_get_args()
		);
	}

	/**
	* @testdox __get($k) calls getOption($k)
	*/
	public function testMagicGet()
	{
		$item = $this->getMockItem('getOption');

		$item->expects($this->once())
		     ->method('getOption')
		     ->with($this->equalTo('foo'));

		$item->foo;
	}

	/**
	* @testdox __set() calls setOption($k, $v)
	*/
	public function testMagicSet()
	{
		$item = $this->getMockItem('setOption');

		$item->expects($this->once())
		     ->method('setOption')
		     ->with($this->equalTo('foo'), $this->equalTo(42));

		$item->foo = 42;
	}

	/**
	* @testdox getOption() throws an InvalidArgumentException if the option does not exist
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Option 'foo' does not exist
	*/
	public function testGetOptionInexistent()
	{
		$item = new ConfigurableItemTestDummy;
		$item->getOption('foo');
	}

	/**
	* @testdox $item->getOption($k) returns $item->$k if it exists
	*/
	public function testGetOption()
	{
		$item = new ConfigurableItemTestDummy;

		$this->assertSame(42, $item->getOption('intOption'));
	}

	/**
	* @testdox $item->getOption($k) returns null if $item->$k is null
	*/
	public function testGetOptionNull()
	{
		$item = new ConfigurableItemTestDummy;

		$this->assertNull($item->getOption('nullOption'));
	}

	/**
	* @testdox getOptions() returns all of the item's properties
	*/
	public function testGetOptions()
	{
		$item = new ConfigurableItemTestDummy;

		$this->assertEquals(
			array(
				'intOption'  => 42,
				'nullOption' => null,
				'collection' => null
			),
			$item->getOptions()
		);
	}

	/**
	* @testdox $item->setOption('foo', 42) calls $item->setFoo(42) if it exists
	*/
	public function testSetOptionSetter()
	{
		$item = $this->getMockItem('setFoo');

		$item->expects($this->once())
		     ->method('setFoo')
		     ->with($this->equalTo(42));

		$item->setOption('foo', 42);
	}

	/**
	* @testdox $item->setOption($k, $v) invokes $item->$k->clear() if $item->$k is a Collection
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
		$item->_setCollection($collection);

		$item->setOption('collection', array());
	}

	/**
	* @testdox $item->setOption($k, $v) invokes $item->$k->add() for every iteration of $v
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
		$item->_setCollection($collection);

		$item->setOption('collection', array(
			'foo' => new ConfigurableItemTestDummy,
			'bar' => new ConfigurableItemTestDummy
		));
	}

	/**
	* @testdox setOption() preserves the type of the option it changes
	*/
	public function testSetOptionPreservesType()
	{
		$item = new ConfigurableItemTestDummy;

		$item->setOption('intOption', '12');

		$this->assertSame(12, $item->getOption('intOption'));
	}

	/**
	* @testdox setOptions($options) calls setOption() for every element in $options
	*/
	public function testSetOptions()
	{
		$item = $this->getMockItem('setOption');

		$item->expects($this->exactly(2))
		     ->method('setOption');

		$item->setOptions(array(
			'intOption'  => 12,
			'nullOption' => null
		));
	}
}

class ConfigurableItemTestDummy extends ConfigurableItem
{
	protected $collection = null;
	protected $intOption  = 42;
	protected $nullOption = null;

	public function _setCollection(Collection $collection)
	{
		$this->collection = $collection;
	}

	static public function normalizeName($name)
	{
		return $name;
	}
}