<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use Exception,
    s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\Collections\FactoryCollection;

include_once __DIR__ . '/../../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\FactoryCollection
*/
class FactoryCollectionTest extends Test
{
	protected function getItemClass()
	{
		return __NAMESPACE__ . '\\FactoryCollectionTestItem';
	}

	protected function newCollection()
	{
		return new FactoryCollectionTestCollection;
	}

	protected function getMockCollection()
	{
		return $this->getMock(
			__NAMESPACE__ . '\\FactoryCollectionTestCollection',
			func_get_args()
		);
	}

	protected function newItem()
	{
		$class = $this->getItemClass();

		return new $class;
	}

	/**
	* @testdox add() creates and returns a new instance of the item class if a second argument is not passed
	*/
	public function testAddCreatesInstanceOnMissingArgument()
	{
		$collection = $this->newCollection();

		$this->assertInstanceOf(
			$this->getItemClass(),
			$collection->add('foo')
		);
	}

	/**
	* @testdox add() creates and returns a new instance of the item class if the second argument is not an instance of the item class
	*/
	public function testAddCreatesInstanceOnNonItemArgument()
	{
		$collection = $this->newCollection();

		$this->assertInstanceOf(
			$this->getItemClass(),
			$collection->add('foo', 'bar')
		);
	}


	/**
	* @testdox add() passes all of its arguments after the first to the item constructor if its second argument is not an instance of the item class
	*/
	public function testAddCreatesInstanceAndPassesAllArguments()
	{
		$collection = $this->newCollection();

		$item = $collection->add('foo', 'bar', 'baz');

		$this->assertSame('bar', $item->a1, 'The first argument does not match');
		$this->assertSame('baz', $item->a2, 'The second argument does not match');
	}

	/**
	* @testdox add() calls normalizeName() with the item name
	*/
	public function testAddCallsNormalizeName()
	{
		$mock = $this->getMockCollection('normalizeName');

		$mock->expects($this->once())
		     ->method('normalizeName')
		     ->with($this->equalTo('foobar'))
		     ->will($this->returnValue('quux'));

		$mock->add('foobar');
	}

	/**
	* @testdox add() uses the value returned by normalizeName() as the item name
	*/
	public function testAddUsesNormalizedName()
	{
		$collection = $this->newCollection();

		$collection->add('FOOBAR');

		$this->assertTrue($collection->exists('foobar'));
	}

	/**
	* @testdox add() throws a RuntimeException if the item name is already is use
	* @expectedException RuntimeException
	* @expectedExceptionMessage Item 'foobar' already exists
	*/
	public function testAddDuplicate()
	{
		$collection = $this->newCollection();

		$collection->add('foobar');
		$collection->add('foobar');
	}

	/**
	* @testdox add() throws a InvalidException if the item name is invalid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid item name 'invalid'
	*/
	public function testAddInvalidName()
	{
		$collection = $this->newCollection();

		// Magic value
		$collection->add('invalid');
	}

	/**
	* @testdox exists() returns TRUE if the item exists
	*/
	public function testExistsTrue()
	{
		$collection = $this->newCollection();
		$collection->add('foobar');

		$this->assertTrue($collection->exists('foobar'));
	}

	/**
	* @testdox exists() returns FALSE if the item does not exist
	*/
	public function testExistsFalse()
	{
		$collection = $this->newCollection();

		$this->assertFalse($collection->exists('foobar'));
	}

	/**
	* @testdox exists() calls normalizeName() with the item name
	*/
	public function testExistsCallsNormalizeName()
	{
		$mock = $this->getMockCollection('normalizeName');

		$mock->expects($this->once())
		     ->method('normalizeName')
		     ->with($this->equalTo('foobar'));

		$mock->exists('foobar');
	}

	/**
	* @testdox exists() uses the value returned by normalizeName() as the item name
	*/
	public function testExistsUsesNormalizedName()
	{
		$collection = $this->newCollection();

		$collection->add('foobar');

		$this->assertTrue($collection->exists('FOOBAR'));
	}

	/**
	* @testdox exists() throws a InvalidException if the item name is invalid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid item name 'invalid'
	*/
	public function testExistsInvalidName()
	{
		$collection = $this->newCollection();

		// Magic value
		$collection->exists('invalid');
	}

	/**
	* @testdox get() returns an item by name
	*/
	public function testGet()
	{
		$item = $this->newItem();

		$collection = $this->newCollection();
		$collection->add('foobar', $item);

		$this->assertSame(
			$item,
			$collection->get('foobar')
		);
	}

	/**
	* @testdox get() calls normalizeName() with the item name
	*/
	public function testGetCallsNormalizeName()
	{
		$mock = $this->getMockCollection('normalizeName');

		$mock->expects($this->once())
		     ->method('normalizeName')
		     ->with($this->equalTo('foobar'));
		try
		{
			$mock->get('foobar');
		}
		catch (Exception $e)
		{
		}
	}

	/**
	* @testdox get() uses the value returned by normalizeName() as the item name
	*/
	public function testGetUsesNormalizedName()
	{
		$collection = $this->newCollection();
		$item = $this->newItem();

		$collection->add('foobar', $item);

		$this->assertSame($item, $collection->get('FOOBAR'));
	}

	/**
	* @testdox get() throws a RuntimeException if the item does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Item 'foobar' does not exist
	*/
	public function testGetInexistent()
	{
		$collection = $this->newCollection();

		$collection->get('foobar');
	}

	/**
	* @testdox get() throws a InvalidException if the item name is invalid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid item name 'invalid'
	*/
	public function testGetInvalidName()
	{
		$collection = $this->newCollection();

		// Magic value
		$collection->get('invalid');
	}

	/**
	* @testdox remove() removes an item by name
	*/
	public function testRemove()
	{
		$collection = $this->newCollection();
		$collection->add('foobar');
		$collection->remove('foobar');

		$this->assertFalse($collection->exists('foobar'));
	}

	/**
	* @testdox remove() calls the item's normalizeName() method with the item name
	*/
	public function testRemoveCallsNormalizeName()
	{
		$mock = $this->getMockCollection('normalizeName');

		$mock->expects($this->once())
		     ->method('normalizeName')
		     ->with($this->equalTo('foobar'));
		try
		{
			$mock->remove('foobar');
		}
		catch (Exception $e)
		{
		}
	}

	/**
	* @testdox remove() uses the value returned by normalizeName() as the item name
	*/
	public function testRemoveUsesNormalizedName()
	{
		$collection = $this->newCollection();
		$item = $this->newItem();

		$collection->add('foobar', $item);
		$collection->remove('FOOBAR');

		$this->assertFalse($collection->exists('foobar'));
	}

	/**
	* @testdox remove() throws a RuntimeException if the item does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Item 'foobar' does not exist
	*/
	public function testRemoveInexistent()
	{
		$collection = $this->newCollection();

		$collection->remove('foobar');
	}

	/**
	* @testdox remove() throws a InvalidException if the item name is invalid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid item name 'invalid'
	*/
	public function testRemoveInvalidName()
	{
		$collection = $this->newCollection();

		// Magic value
		$collection->remove('invalid');
	}
}

class FactoryCollectionTestCollection extends FactoryCollection
{
	protected function getItemClass()
	{
		return __NAMESPACE__ . '\\FactoryCollectionTestItem';
	}

	public function isValidName($name)
	{
		return ($name !== 'invalid');
	}

	public function normalizeName($name)
	{
		return strtolower($name);
	}
}

class FactoryCollectionTestItem
{
	public function __construct($a1 = null, $a2 = null)
	{
		$this->a1 = $a1;
		$this->a2 = $a2;
	}
}