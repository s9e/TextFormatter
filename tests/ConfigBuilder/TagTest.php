<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder;

use Exception,
    s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\Collection,
    s9e\TextFormatter\ConfigBuilder\Tag;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder\Tag
*/
class TagTest extends Test
{
	/**
	* @testdox $tag->attributes is a Collection
	*/
	public function testAddCreatesInstanceOnMissingArgument()
	{
		$class = __NAMESPACE__ . '\\TagTestItem';
		$collection = new Tag($class);

		$this->assertInstanceOf(
			$class,
			$collection->add('foo')
		);
	}

	/**
	* @testdox add() creates and returns a new instance of the Item class if the second argument is not an instance of the Item class
	*/
	public function testAddCreatesInstanceOnNonItemArgument()
	{
		$class = __NAMESPACE__ . '\\TagTestItem';
		$collection = new Tag($class);

		$this->assertInstanceOf(
			$class,
			$collection->add('foo', 'bar')
		);
	}


	/**
	* @testdox add() passes all of its arguments after the first to the Item constructor if its second argument is not an instance of the Item class
	*/
	public function testAddCreatesInstanceAndPassesAllArguments()
	{
		$class = __NAMESPACE__ . '\\TagTestItem';
		$collection = new Tag($class);

		$item = $collection->add('foo', 'bar', 'baz');

		$this->assertSame('bar', $item->a1, 'The first argument does not match');
		$this->assertSame('baz', $item->a2, 'The second argument does not match');
	}

	/**
	* @testdox add() calls the item's normalizeName() method
	*/
	public function testAddCallsNormalizeNameIsCalled()
	{
		$class = $this->getMockItemClass();

		$class::staticExpects($this->once())
		      ->method('normalizeName')
		      ->with($this->equalTo('foobar'));

		$collection = new Tag($class);
		$collection->add('foobar');
	}

	/**
	* @testdox get() throws a RuntimeException if the item already exists
	* @expectedException RuntimeException
	* @expectedExceptionMessage Item 'foobar' already exists
	*/
	public function testAddDuplicate()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');

		$collection->add('foobar');
		$collection->add('foobar');
	}

	/**
	* @testdox exists() returns TRUE if the item exists
	*/
	public function testExistsTrue()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');
		$collection->add('foobar');

		$this->assertTrue($collection->exists('foobar'));
	}

	/**
	* @testdox exists() returns FALSE if the item does not exist
	*/
	public function testExistsFalse()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');

		$this->assertFalse($collection->exists('foobar'));
	}

	/**
	* @testdox exists() calls the item's normalizeName() method
	*/
	public function testExistsCallsNormalizeNameIsCalled()
	{
		$class = $this->getMockItemClass();

		$class::staticExpects($this->once())
		      ->method('normalizeName')
		      ->with($this->equalTo('foobar'));

		$collection = new Tag($class);
		$collection->exists('foobar');
	}

	/**
	* @testdox get() returns an item by name
	*/
	public function testGet()
	{
		$class = __NAMESPACE__ . '\\TagTestItem';
		$item  = new $class;

		$collection = new Tag($class);
		$collection->add('foobar', $item);

		$this->assertSame(
			$item,
			$collection->get('foobar')
		);
	}

	/**
	* @testdox get() calls the item's normalizeName() method
	*/
	public function testGetCallsNormalizeNameIsCalled()
	{
		$class = $this->getMockItemClass();

		$class::staticExpects($this->once())
		      ->method('normalizeName')
		      ->with($this->equalTo('foobar'));

		$collection = new Tag($class);

		try
		{
			$collection->get('foobar');
		}
		catch (Exception $e)
		{
		}
	}

	/**
	* @testdox get() throws a RuntimeException if the item does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Item 'foobar' does not exist
	*/
	public function testGetInexistent()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');

		$collection->get('foobar');
	}

	/**
	* @testdox remove() removes an item by name
	*/
	public function testRemove()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');
		$collection->add('foobar');
		$collection->remove('foobar');

		$this->assertFalse($collection->exists('foobar'));
	}

	/**
	* @testdox remove() calls the item's normalizeName() method
	*/
	public function testRemoveCallsNormalizeNameIsCalled()
	{
		$class = $this->getMockItemClass();

		$class::staticExpects($this->once())
		      ->method('normalizeName')
		      ->with($this->equalTo('foobar'));

		$collection = new Tag($class);

		try
		{
			$collection->remove('foobar');
		}
		catch (Exception $e)
		{
		}
	}

	/**
	* @testdox remove() throws a RuntimeException if the item does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Item 'foobar' does not exist
	*/
	public function testRemoveInexistent()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');

		$collection->remove('foobar');
	}

	/**
	* @testdox clear() removes all items from the collection
	*/
	public function testClear()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');
		$collection->add('foobar');
		$collection->add('foobarbaz');

		$collection->clear();

		$this->assertFalse($collection->exists('foobar'));
		$this->assertFalse($collection->exists('foobarbaz'));
	}

	/**
	* @testdox rename() creates an entry for the new name
	*/
	public function testRenameCreatesNew()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');

		$collection->add('foobar');
		$collection->rename('foobar', 'foobarbaz');

		$this->assertTrue($collection->exists('foobarbaz'));
	}

	/**
	* @testdox rename() removes the entry for the old name
	*/
	public function testRenameRemovesOld()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');

		$collection->add('foobar');
		$collection->rename('foobar', 'foobarbaz');

		$this->assertFalse($collection->exists('foobar'));
	}

	/**
	* @testdox rename() throws a RuntimeException if the item does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Item 'foobar' does not exist
	*/
	public function testRenameInexistent()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');

		$collection->rename('foobar', 'foobarbaz');
	}

	/**
	* @testdox rename() throws a RuntimeException if the new name is already taken
	* @expectedException RuntimeException
	* @expectedExceptionMessage Item 'foobarbaz' already exists
	*/
	public function testRenameDuplicate()
	{
		$collection = new Tag(__NAMESPACE__ . '\\TagTestItem');

		$collection->add('foobar');
		$collection->add('foobarbaz');
		$collection->rename('foobar', 'foobarbaz');
	}

	/**
	* @testdox Tag is iterable with foreach, with item names as keys and items as values
	*/
	public function testForeach()
	{
		$class = __NAMESPACE__ . '\\TagTestItem';
		$item1 = new $class;
		$item2 = new $class;

		$collection = new Tag($class);
		$collection->add('i1', $item1);
		$collection->add('i2', $item2);

		$items = array();
		foreach ($collection as $k => $v)
		{
			$items[$k] = $v;
		}

		$this->assertEquals(
			array('i1' => $item1, 'i2' => $item2),
			$items
		);
	}
}

class TagTestItem implements Item
{
	public function __construct($a1 = null, $a2 = null)
	{
		$this->a1 = $a1;
		$this->a2 = $a2;
	}

	static public function normalizeName($name)
	{
		return $name;
	}
}