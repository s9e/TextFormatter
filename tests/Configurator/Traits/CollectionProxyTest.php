<?php

namespace s9e\TextFormatter\Tests\Configurator\Traits;

use ArrayAccess;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Traits\CollectionProxy
*/
class CollectionProxyTest extends Test
{
	protected $mock;
	protected $proxy;

	protected function setUp(): void
	{
		$this->mock = $this->getMockBuilder(
			's9e\\TextFormatter\\Configurator\\Collections\\NormalizedCollection'
		)->getMock();

		$this->proxy = new CollectionProxyDummy($this->mock);
	}

	/**
	* @testdox $proxy->add() gets forwarded to $proxy->collection->add() with all arguments
	*/
	public function testAdd()
	{
		$this->mock->expects($this->once())
		           ->method('add')
		           ->with($this->equalTo('foo'), $this->equalTo('bar'));

		$this->proxy->add('foo', 'bar');
	}

	/**
	* @testdox $proxy->add() returns the value returned by $proxy->collection->add()
	*/
	public function testAddReturn()
	{
		$this->mock->expects($this->once())
		           ->method('add')
		           ->will($this->returnValue('foobar'));

		$this->assertSame('foobar', $this->proxy->add('foo', 'bar'));
	}

	/**
	* @testdox $proxy->exists() gets forwarded to $proxy->collection->exists() with all arguments
	*/
	public function testExists()
	{
		$this->mock->expects($this->once())
		           ->method('exists')
		           ->with($this->equalTo('foo'), $this->equalTo('bar'));

		$this->proxy->exists('foo', 'bar');
	}

	/**
	* @testdox $proxy->delete() gets forwarded to $proxy->collection->delete() with all arguments
	*/
	public function testDelete()
	{
		$this->mock->expects($this->once())
		           ->method('delete')
		           ->with($this->equalTo('foo'), $this->equalTo('bar'));

		$this->proxy->delete('foo', 'bar');
	}

	/**
	* @testdox $proxy->get() gets forwarded to $proxy->collection->get() with all arguments
	*/
	public function testGet()
	{
		$this->mock->expects($this->once())
		           ->method('get')
		           ->with($this->equalTo('foo'), $this->equalTo('bar'));

		$this->proxy->get('foo', 'bar');
	}

	/**
	* @testdox $proxy->get() returns the value returned by $proxy->collection->get()
	*/
	public function testGetReturn()
	{
		$this->mock->expects($this->once())
		           ->method('get')
		           ->will($this->returnValue('foobar'));

		$this->assertSame('foobar', $this->proxy->get('foo'));
	}

	/**
	* @testdox $proxy->set() gets forwarded to $proxy->collection->set() with all arguments
	*/
	public function testSet()
	{
		$this->mock->expects($this->once())
		           ->method('set')
		           ->with($this->equalTo('foo'), $this->equalTo('bar'));

		$this->proxy->set('foo', 'bar');
	}

	/**
	* @testdox $proxy->set() returns the value returned by $proxy->collection->set()
	*/
	public function testSetReturn()
	{
		$this->mock->expects($this->once())
		           ->method('set')
		           ->will($this->returnValue('foobar'));

		$this->assertSame('foobar', $this->proxy->set('foo', 'bar'));
	}

	/**
	* @testdox $proxy['foo'] returns $proxy->collection['foo']
	*/
	public function testOffsetGet()
	{
		$this->mock->expects($this->once())
		           ->method('OffsetGet')
		           ->with($this->equalTo('foo'))
		           ->will($this->returnValue('bar'));

		$this->assertSame('bar', $this->proxy['foo']);
	}

	/**
	* @testdox $proxy['foo'] = 42 sets $proxy->collection['foo'] = 42
	*/
	public function testOffsetSet()
	{
		$this->mock->expects($this->once())
		           ->method('OffsetSet')
		           ->with($this->equalTo('foo'), $this->equalTo(42));

		$this->proxy['foo'] = 42;
	}

	/**
	* @testdox isset($proxy['foo']) returns isset($proxy->collection['foo'])
	*/
	public function testOffsetExists()
	{
		$this->mock->expects($this->exactly(2))
		           ->method('OffsetExists')
		           ->with($this->equalTo('foo'))
		           ->will($this->onConsecutiveCalls(true, false));

		$this->assertTrue(isset($this->proxy['foo']));
		$this->assertFalse(isset($this->proxy['foo']));
	}

	/**
	* @testdox unset($proxy['foo']) calls unset($proxy->collection['foo'])
	*/
	public function testOffsetUnset()
	{
		$this->mock->expects($this->once())
		           ->method('OffsetUnset')
		           ->with($this->equalTo('foo'));

		unset($this->proxy['foo']);
	}

	/**
	* @testdox count($proxy) returns count($proxy->collection)
	*/
	public function testCount()
	{
		$this->mock->expects($this->once())
		           ->method('count')
		           ->will($this->returnValue(42));

		$this->assertSame(42, count($this->proxy));
	}

	/**
	* @testdox A collection proxy is iterable with foreach
	*/
	public function testIterable()
	{
		$collection = new NormalizedCollection;
		$collection->add('one', 1);
		$collection->add('two', 2);

		$proxy = new CollectionProxyDummy($collection);

		$actual = [];
		foreach ($proxy as $k => $v)
		{
			$actual[$k] = $v;
		}

		$this->assertSame(
			['one' => 1, 'two' => 2],
			$actual
		);
	}
}

class CollectionProxyDummy implements ArrayAccess, Countable, Iterator
{
	use CollectionProxy;

	protected $collection;

	public function __construct($collection)
	{
		$this->collection = $collection;
	}
}