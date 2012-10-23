<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use Exception;
use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;

/**
* @covers s9e\TextFormatter\Configurator\Collections\NormalizedCollection
*/
class NormalizedCollectionTest extends Test
{
	protected function getMockCollection()
	{
		return $this->getMock(
			's9e\\TextFormatter\\Configurator\\Collections\\NormalizedCollection',
			func_get_args()
		);
	}

	/**
	* @testdox set() calls normalizeKey()
	*/
	public function testSetCallsNormalizeKey()
	{
		$mock = $this->getMockCollection('normalizeKey');

		$mock->expects($this->once())
		     ->method('normalizeKey')
		     ->with($this->equalTo('foobar'));

		$mock->set('foobar', 42);
	}

	/**
	* @testdox set() calls normalizeValue()
	*/
	public function testSetCallsNormalizeValue()
	{
		$mock = $this->getMockCollection('normalizeValue');

		$mock->expects($this->once())
		     ->method('normalizeValue')
		     ->with($this->equalTo(42));

		$mock->set('foobar', 42);
	}

	/**
	* @testdox add() calls normalizeKey()
	*/
	public function testAddCallsNormalizeKey()
	{
		$mock = $this->getMockCollection('normalizeKey');

		$mock->expects($this->atLeastOnce())
		     ->method('normalizeKey')
		     ->with($this->equalTo('foobar'));

		$mock->add('foobar', 42);
	}

	/**
	* @testdox add() calls normalizeValue()
	*/
	public function testAddCallsNormalizeValue()
	{
		$mock = $this->getMockCollection('normalizeValue');

		$mock->expects($this->atLeastOnce())
		     ->method('normalizeValue')
		     ->with($this->equalTo(42));

		$mock->add('foobar', 42);
	}

	/**
	* @testdox add() can be called without a second parameter
	*/
	public function testAddWithNoValue()
	{
		$collection = new NormalizedCollection;
		$collection->add('foobar');
	}

	/**
	* @testdox get() throws a RuntimeException if the item already exists
	* @expectedException RuntimeException
	* @expectedExceptionMessage Item 'foobar' already exists
	*/
	public function testAddDuplicate()
	{
		$collection = new NormalizedCollection;

		$collection->add('foobar');
		$collection->add('foobar');
	}

	/**
	* @testdox exists() returns TRUE if the item exists
	*/
	public function testExistsTrue()
	{
		$collection = new NormalizedCollection;
		$collection->add('foobar');

		$this->assertTrue($collection->exists('foobar'));
	}

	/**
	* @testdox exists() returns FALSE if the item does not exist
	*/
	public function testExistsFalse()
	{
		$collection = new NormalizedCollection;

		$this->assertFalse($collection->exists('foobar'));
	}

	/**
	* @testdox exists() calls normalizeKey()
	*/
	public function testExistsCallsNormalizeKey()
	{
		$mock = $this->getMockCollection('normalizeKey');

		$mock->expects($this->once())
		     ->method('normalizeKey')
		     ->with($this->equalTo('foobar'));

		$mock->exists('foobar');
	}

	/**
	* @testdox get() returns an item by name
	*/
	public function testGet()
	{
		$collection = new NormalizedCollection;
		$collection->add('foobar', 42);

		$this->assertSame(42, $collection->get('foobar'));
	}

	/**
	* @testdox get() calls normalizeKey()
	*/
	public function testGetCallsNormalizeKey()
	{
		$mock = $this->getMockCollection('normalizeKey');

		$mock->expects($this->once())
		     ->method('normalizeKey')
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
	* @testdox get() throws a RuntimeException if the item does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Item 'foobar' does not exist
	*/
	public function testGetInexistent()
	{
		$collection = new NormalizedCollection;

		$collection->get('foobar');
	}

	/**
	* @testdox delete() removes an item by name
	*/
	public function testDelete()
	{
		$collection = new NormalizedCollection;
		$collection->add('foobar');
		$collection->delete('foobar');

		$this->assertFalse($collection->exists('foobar'));
	}

	/**
	* @testdox delete() calls the item's normalizeKey() method
	*/
	public function testDeleteCallsNormalizeKey()
	{
		$mock = $this->getMockCollection('normalizeKey');

		$mock->expects($this->once())
		     ->method('normalizeKey')
		     ->with($this->equalTo('foobar'));
		try
		{
			$mock->delete('foobar');
		}
		catch (Exception $e)
		{
		}
	}

	/**
	* @testdox isset($collection['foo']) maps to $collection->exists('foo')
	*/
	public function testOffsetExists()
	{
		$mock = $this->getMockCollection('exists');

		$mock->expects($this->once())
		     ->method('exists')
		     ->with($this->equalTo('foo'));

		isset($mock['foo']);
	}

	/**
	* @testdox $collection['foo'] maps to $collection->get('foo')
	*/
	public function testOffsetGet()
	{
		$mock = $this->getMockCollection('get');

		$mock->expects($this->once())
		     ->method('get')
		     ->with($this->equalTo('foo'));

		$mock['foo'];
	}

	/**
	* @testdox $collection['foo'] = 42 maps to $collection->set('foo', 42)
	*/
	public function testOffsetSet()
	{
		$mock = $this->getMockCollection('set');

		$mock->expects($this->once())
		     ->method('set')
		     ->with($this->equalTo('foo'), $this->equalTo(42));

		$mock['foo'] = 42;
	}

	/**
	* @testdox unset($collection['foo']) maps to $collection->delete('foo')
	*/
	public function testOffsetUnset()
	{
		$mock = $this->getMockCollection('delete');

		$mock->expects($this->once())
		     ->method('delete')
		     ->with($this->equalTo('foo'));

		unset($mock['foo']);
	}
}