<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\FilterChain;
use s9e\TextFormatter\ConfigBuilder\Items\CallbackTemplate;
use s9e\TextFormatter\ConfigBuilder\Items\Filter;
use s9e\TextFormatter\ConfigBuilder\Items\FilterLink;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\FilterChain
*/
class FilterChainTest extends Test
{
	public $filterChain;

	private function privateMethod() {}

	public function setUp()
	{
		$this->filterChain = new FilterChain(array());
	}

	public function doNothing() {}

	/**
	* @testdox append() adds the filter at the end of the chain
	*/
	public function testAppend()
	{
		$int = new Filter('#int');
		$url = new Filter('#url');

		$this->filterChain->append($int);
		$this->filterChain->append($url);

		$this->assertSame($int, $this->filterChain[0]);
		$this->assertSame($url, $this->filterChain[1]);
	}

	/**
	* @testdox prepend() adds the filter at the beginning of the chain
	*/
	public function testPrepend()
	{
		$int = new Filter('#int');
		$url = new Filter('#url');

		$this->filterChain->prepend($url);
		$this->filterChain->prepend($int);

		$this->assertSame($int, $this->filterChain[0]);
		$this->assertSame($url, $this->filterChain[1]);
	}

	/**
	* @testdox append() correctly records filter vars
	*/
	public function testAppendFilterVars()
	{
		$vars = array('min' => 0, 'max' => 5);

		$this->filterChain->append('#range', $vars);

		$this->assertSame($vars, $this->filterChain[0]->getVars());
	}

	/**
	* @testdox prepend() correctly records filter vars
	*/
	public function testPrependFilterVars()
	{
		$vars = array('min' => 0, 'max' => 5);

		$this->filterChain->prepend('#range', $vars);

		$this->assertSame($vars, $this->filterChain[0]->getVars());
	}

	/**
	* @testdox append() throws an InvalidArgumentException on invalid callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Filter '*invalid*' is neither callable or the reference to a built-in filter
	*/
	public function testAppendInvalidCallback()
	{
		$this->filterChain->append('*invalid*');
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on invalid callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Filter '*invalid*' is neither callable or the reference to a built-in filter
	*/
	public function testPrependInvalidCallback()
	{
		$this->filterChain->prepend('*invalid*');
	}

	/**
	* @testdox append() throws an InvalidArgumentException on uncallable callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage is neither callable or the reference to a built-in filter
	*/
	public function testAppendUncallableCallback()
	{
		$this->filterChain->append(array(__CLASS__, 'privateMethod'));
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on uncallable callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage is neither callable or the reference to a built-in filter
	*/
	public function testPrependUncallableCallback()
	{
		$this->filterChain->prepend(array(__CLASS__, 'privateMethod'));
	}

	/**
	* @testdox $filterChain[] = 'foo' maps to $filterChain->append('foo')
	*/
	public function testArrayAccessAppend()
	{
		$mock = $this->getMock(
			's9e\\TextFormatter\\ConfigBuilder\\Collections\\FilterChain',
			array('append'),
			array(array())
		);

		$mock->expects($this->once())
		     ->method('append')
		     ->with($this->equalTo('foo'));

		$mock[] = 'foo';
	}

	/**
	* @testdox PHP string callbacks are normalized to an instance of s9e\TextFormatter\ConfigBuilder\Items\Filter
	*/
	public function testStringCallback()
	{
		$this->filterChain->append('strtolower');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\Filter',
			$this->filterChain[0]
		);
	}

	/**
	* @testdox PHP array callbacks are normalized to an instance of s9e\TextFormatter\ConfigBuilder\Items\Filter
	*/
	public function testArrayCallback()
	{
		$this->filterChain->append(array($this, 'doNothing'));

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\Filter',
			$this->filterChain[0]
		);
	}

	/**
	* @testdox Instances of s9e\TextFormatter\ConfigBuilder\Items\CallbackTemplate are normalized to an instance of s9e\TextFormatter\ConfigBuilder\Items\Filter
	*/
	public function testArrayCallbackTemplate()
	{
		$callback = new CallbackTemplate('strtolower');
		$this->filterChain->append($callback);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\Filter',
			$this->filterChain[0]
		);
	}

	/**
	* @testdox Instances of s9e\TextFormatter\ConfigBuilder\Items\Filter are added as-is
	*/
	public function testFilterInstance()
	{
		$filter = new Filter('#int');
		$this->filterChain->append($filter);

		$this->assertSame(
			$filter,
			$this->filterChain[0]
		);
	}

	/**
	* @testdox contains() returns false if the given filter is not present in the chain
	*/
	public function testNegativeContains()
	{
		$this->filterChain->append('#int');

		$this->assertFalse($this->filterChain->contains('#url'));
	}

	/**
	* @testdox contains() returns true if the given built-in filter is present in the chain
	*/
	public function testContainsBuiltIn()
	{
		$this->filterChain->append('#int');

		$this->assertTrue($this->filterChain->contains('#int'));
	}

	/**
	* @testdox contains() returns true if the given PHP string callback is present in the chain
	*/
	public function testContainsStringCallback()
	{
		$this->filterChain->append('strtolower');

		$this->assertTrue($this->filterChain->contains('strtolower'));
	}

	/**
	* @testdox $filterChain['foo'] = 'strtolower' throws an InvalidArgumentException
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid offset 'foo'
	*/
	public function testArrayAccessInvalidKey()
	{
		$this->filterChain['foo'] = 'strtolower';
	}

	/**
	* @testdox Deleting a filter reorders the chain to remove gaps
	*/
	public function testDeleteReordersChain()
	{
		$int = new Filter('#int');
		$url = new Filter('#url');

		$this->filterChain->append($int);
		$this->filterChain->append($url);

		$this->filterChain->delete(0);

		$this->assertSame(1, count($this->filterChain));
		$this->assertSame($url, $this->filterChain[0]);
	}
}