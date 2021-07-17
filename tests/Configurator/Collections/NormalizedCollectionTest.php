<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use Exception;
use InvalidArgumentException;
use stdClass;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\NormalizedCollection
*/
class NormalizedCollectionTest extends Test
{
	protected function getMockCollection()
	{
		return $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\Collections\\NormalizedCollection')->setMethods(func_get_args())->getMock();
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
	* @doesNotPerformAssertions
	*/
	public function testAddWithNoValue()
	{
		$collection = new NormalizedCollection;
		$collection->add('foobar');
	}

	/**
	* @testdox add() throws a RuntimeException if the item already exists
	*/
	public function testAddDuplicate()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Item 'foobar' already exists");

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
	* @testdox exists() ignores InvalidArgumentException from normalizeKey()
	*/
	public function testExistsInvalidArgumentException()
	{
		$collection = new DummyNormalizedCollection([]);
		$this->assertFalse($collection->exists('invalid'));
		$this->assertFalse(isset($collection['invalid']));
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
	*/
	public function testGetInexistent()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Item 'foobar' does not exist");

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
	* @testdox delete() ignores InvalidArgumentException from normalizeKey()
	* @doesNotPerformAssertions
	*/
	public function testDeleteInvalidArgumentException()
	{
		$collection = new DummyNormalizedCollection([]);
		$collection->delete('invalid');
		unset($collection['invalid']);
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

	/**
	* @testdox contains() returns true if the given value is present in the collection
	*/
	public function testPositiveContains()
	{
		$collection = new DummyNormalizedCollection(['a' => 1, 'b' => 2]);

		$this->assertTrue($collection->contains(1));
	}

	/**
	* @testdox contains() returns false if the given value is not present in the collection
	*/
	public function testNegativeContains()
	{
		$collection = new DummyNormalizedCollection(['a' => 1, 'b' => 2]);

		$this->assertFalse($collection->contains(4));
	}

	/**
	* @testdox contains() checks for equality, not identity
	*/
	public function testEqualityContains()
	{
		$collection = new DummyNormalizedCollection(['a' => new stdClass]);

		$this->assertTrue($collection->contains(new stdClass));
	}

	/**
	* @testdox indexOf() returns the key if the given value is present in the collection
	*/
	public function testPositiveIndexOf()
	{
		$collection = new DummyNormalizedCollection(['a' => 1, 'b' => 2]);

		$this->assertSame('a', $collection->indexOf(1));
	}

	/**
	* @testdox indexOf() returns false if the given value is not present in the collection
	*/
	public function testNegativeIndexOf()
	{
		$collection = new DummyNormalizedCollection(['a' => 1, 'b' => 2]);

		$this->assertFalse($collection->indexOf(4));
	}

	/**
	* @testdox indexOf() checks for equality, not identity
	*/
	public function testEqualityIndexOf()
	{
		$collection = new DummyNormalizedCollection(['a' => new stdClass]);

		$this->assertSame('a', $collection->indexOf(new stdClass));
	}

	/**
	* @testdox onDuplicate() can be called with no value
	* @doesNotPerformAssertions
	*/
	public function testOnDuplicateNoValue()
	{
		$collection = new NormalizedCollection;
		$collection->onDuplicate();
	}

	/**
	* @testdox onDuplicate() returns 'error' by default
	*/
	public function testOnDuplicateDefault()
	{
		$collection = new NormalizedCollection;
		$this->assertSame('error', $collection->onDuplicate());
	}

	/**
	* @testdox onDuplicate() returns the previous value
	*/
	public function testOnDuplicateReturn()
	{
		$collection = new NormalizedCollection;
		$this->assertSame('error', $collection->onDuplicate('replace'));
	}

	/**
	* @testdox onDuplicate('unknownvalue') throws an exception
	*/
	public function testOnDuplicateInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Invalid onDuplicate action');

		$collection = new NormalizedCollection;
		$collection->onDuplicate('unknownvalue');
	}

	/**
	* @testdox add() has no effect on duplicates if the onDuplicate action is "ignore"
	*/
	public function testOnDuplicateIgnore()
	{
		$collection = new NormalizedCollection;
		$collection->onDuplicate('ignore');

		$collection->add('foo', 'bar');
		$collection->add('foo', 'baz');

		$this->assertSame('bar', $collection->get('foo'));
	}

	/**
	* @testdox add() returns the original element when trying to add a duplicate and the onDuplicate action is "ignore"
	*/
	public function testOnDuplicateIgnoreReturn()
	{
		$collection = new NormalizedCollection;
		$collection->onDuplicate('ignore');

		$collection->add('foo', 'bar');

		$this->assertSame('bar', $collection->add('foo', 'baz'));
	}

	/**
	* @testdox add() replaces the original element if the onDuplicate action is "replace"
	*/
	public function testOnDuplicateReplace()
	{
		$collection = new NormalizedCollection;
		$collection->onDuplicate('replace');

		$collection->add('foo', 'bar');
		$collection->add('foo', 'baz');

		$this->assertSame('baz', $collection->get('foo'));
	}

	/**
	* @testdox add() throws a RuntimeException on duplicate elements if the onDuplicate action is "error"
	*/
	public function testOnDuplicateError()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Item 'foo' already exists");

		$collection = new NormalizedCollection;
		$collection->onDuplicate('error');

		$collection->add('foo', 'bar');
		$collection->add('foo', 'baz');
	}

	/**
	* @testdox asConfig() returns the elements in lexical order
	*/
	public function testAsConfigLexicalOrder()
	{
		$collection = new NormalizedCollection;
		$collection->add('foo', 'foo');
		$collection->add('bar', 'bar');

		$this->assertSame(['bar' => 'bar', 'foo' => 'foo'], $collection->asConfig());
	}
}

class DummyNormalizedCollection extends NormalizedCollection
{
	public function __construct(array $items)
	{
		$this->items = $items;
	}

	public function normalizeKey($key)
	{
		if ($key === 'invalid')
		{
			throw new InvalidArgumentException;
		}

		return $key;
	}
}