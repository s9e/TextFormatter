<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder;

use Exception,
    s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\Filter,
    s9e\TextFormatter\ConfigBuilder\FilterChain;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder\FilterChain
*/
class FilterChainTest extends Test
{
	static public function staticMethod() {}
	public function method() {}

	public function setUp()
	{
		$this->filterChain = new FilterChain(array('attrVal' => null));
	}

	/**
	* @testdox append() and prepend() accept default filters such as '#int'
	*/
	public function testAppendDefaultFilter()
	{
		$this->filterChain->append('#int');
		$this->filterChain->prepend('#int');
	}

	/**
	* @testdox append() and prepend() accept simple callbacks such as 'strtolower'
	*/
	public function testAppendStringCallback()
	{
		$this->filterChain->append('strtolower');
		$this->filterChain->prepend('strtolower');
	}

	/**
	* @testdox append() and prepend() accept static class method calls such as array('Class', 'staticMethod')
	*/
	public function testAppendStaticMethodCallback()
	{
		$this->filterChain->append(array(__CLASS__, 'staticMethod'));
		$this->filterChain->prepend(array(__CLASS__, 'staticMethod'));
	}

	/**
	* @testdox append() and prepend() accept static class method calls such as array('Class::staticMethod')
	*/
	public function testAppendStaticMethodAsStringCallback()
	{
		$this->filterChain->append(__CLASS__ . '::staticMethod');
		$this->filterChain->prepend(__CLASS__ . '::staticMethod');
	}

	/**
	* @testdox append() and prepend() accept object method calls such as array($this, 'method')
	*/
	public function testAppendObjectMethodCallback()
	{
		$this->filterChain->append(array($this, 'method'));
		$this->filterChain->prepend(array($this, 'method'));
	}

	/**
	* @testdox append() and prepend() accept Filter objects
	*/
	public function testAppendFilterObject()
	{
		$this->filterChain->append(new Filter('strtolower'));
		$this->filterChain->prepend(new Filter('strtolower'));
	}

	/**
	* @testdox append() and prepend() accept closures
	*/
	public function testAppendClosure()
	{
		$this->filterChain->append(function() {});
		$this->filterChain->prepend(function() {});
	}

	/**
	* @testdox append() throws a InvalidArgumentException if its argument is not callable
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback '1' is not callable
	*/
	public function testAppendException()
	{
		$this->filterChain->append(1);
	}

	/**
	* @testdox prepend() throws a InvalidArgumentException if its argument is not callable
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback '1' is not callable
	*/
	public function testPrependException()
	{
		$this->filterChain->prepend(1);
	}

	/**
	* @testdox get() returns all filters in an array
	*/
	public function testGet()
	{
		$this->assertSame(
			array(),
			$this->filterChain->get()
		);
	}

	/**
	* @testdox get() returns default filters such as '#int' as strings
	*/
	public function testGetDefaultFilter()
	{
		$this->filterChain->append('#int');

		$this->assertSame(
			array('#int'),
			$this->filterChain->get()
		);
	}

	/**
	* @testdox get() returns other filters as Filter objects
	*/
	public function testGetOtherFilters()
	{
		$this->filterChain->append('strtolower');
		$this->filterChain->append(array($this, 'method'));
		$this->filterChain->append(function() {});

		$get = $this->filterChain->get();

		$this->assertSame(3, count($get));

		foreach ($get as $filter)
		{
			$this->assertInstanceOf(
				's9e\\TextFormatter\\ConfigBuilder\\Filter',
				$filter
			);
		}
	}

	/**
	* @testdox clear() removes all filters
	*/
	public function testClear()
	{
		$this->filterChain->append('#int');
		$this->filterChain->clear();

		$this->assertSame(
			array(),
			$this->filterChain->get()
		);

	}

	/**
	* @testdox has('#int') returns true if '#int' is in the chain
	*/
	public function testHasDefaultFilterTrue()
	{
		$this->filterChain->append('#int');

		$this->assertTrue($this->filterChain->has('#int'));
	}

	/**
	* @testdox has('#int') returns false if '#number' only is in the chain
	*/
	public function testHasDefaultFilterFalse()
	{
		$this->filterChain->append('#number');

		$this->assertFalse($this->filterChain->has('#int'));
	}

	/**
	* @testdox has('strtolower') returns true if 'strtolower' is in the chain
	*/
	public function testHasStringCallbackTrue()
	{
		$this->filterChain->append('strtolower');

		$this->assertTrue($this->filterChain->has('strtolower'));
	}

	/**
	* @testdox has('strtolower') returns false if only 'trim' is in the chain
	*/
	public function testHasStringCallbackFalse()
	{
		$this->filterChain->append('trim');

		$this->assertFalse($this->filterChain->has('strtolower'));
	}

	/**
	* @testdox has($FilterObject) returns true if $FilterObject is in the chain
	*/
	public function testHasFilterObjectTrue()
	{
		$FilterObject = new Filter('trim');

		$this->filterChain->append($FilterObject);

		$this->assertTrue($this->filterChain->has($FilterObject));
	}

	/**
	* @testdox has($FilterObject) returns true if $FilterObjectClone is in the chain
	*/
	public function testHasFilterObjectCloneTrue()
	{
		$FilterObject      = new Filter('trim');
		$FilterObjectClone = clone $FilterObject;

		$this->filterChain->append($FilterObjectClone);

		$this->assertTrue($this->filterChain->has($FilterObject));
	}

	/**
	* @testdox has($FilterObject) returns false if only $SomeOtherFilterObject is in the chain
	*/
	public function testHasFilterObjectFalse()
	{
		$FilterObject          = new Filter('trim');
		$SomeOtherFilterObject = new Filter('strtolower');

		$this->filterChain->append($SomeOtherFilterObject);

		$this->assertFalse($this->filterChain->has($FilterObject));
	}

	/**
	* @testdox FilterChain is iterable with foreach, with filter position key and filter as value
	*/
	public function testForeach()
	{
		$this->filterChain->append('#int');
		$this->filterChain->append('#number');

		$filters = array();
		foreach ($this->filterChain as $k => $v)
		{
			$filters[$k] = $v;
		}

		$this->assertSame(
			array('#int', '#number'),
			$filters
		);
	}
}