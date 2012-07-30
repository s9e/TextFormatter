<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\FilterChain;
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
	* @testdox append() throws an InvalidArgumentException on invalid callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Filter '*invalid*' is not callable
	*/
	public function testAppendInvalidCallback()
	{
		$this->filterChain->append('*invalid*');
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on invalid callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Filter '*invalid*' is not callable
	*/
	public function testPrependInvalidCallback()
	{
		$this->filterChain->prepend('*invalid*');
	}

	/**
	* @testdox append() throws an InvalidArgumentException on uncallable callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage is not callable
	*/
	public function testAppendUncallableCallback()
	{
		$this->filterChain->append(array(__CLASS__, 'privateMethod'));
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on uncallable callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage is not callable
	*/
	public function testPrependUncallableCallback()
	{
		$this->filterChain->prepend(array(__CLASS__, 'privateMethod'));
	}

	/**
	* @testdox PHP string callbacks are normalized to an instance of s9e\TextFormatter\ConfigBuilder\Items\Filter
	*/
	public function testStringCallback()
	{
		$this->filterChain->append(array($this, 'doNothing'));

		$filterLinks = iterator_to_array($this->filterChain);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\Filter',
			$filterLinks[0]->getFilter()
		);
	}

	/**
	* @testdox PHP array callbacks are normalized to an instance of s9e\TextFormatter\ConfigBuilder\Items\Filter
	*/
	public function testArrayCallback()
	{
		$this->filterChain->append('strtolower');

		$filterLinks = iterator_to_array($this->filterChain);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\Filter',
			$filterLinks[0]->getFilter()
		);
	}

	/**
	* @testdox Instances of s9e\TextFormatter\ConfigBuilder\Items\FilterLink are added as-is
	*/
	public function testFilterLinkInstance()
	{
		$filter     = new Filter('strtolower');
		$filterLink = new FilterLink($filter, array());
		$this->filterChain->append($filterLink);

		$filterLinks = iterator_to_array($this->filterChain);

		$this->assertSame(
			$filterLink,
			$filterLinks[0]
		);
	}

	/**
	* @testdox Instances of s9e\TextFormatter\ConfigBuilder\Items\Filter are added as-is
	*/
	public function testFilterInstance()
	{
		$filter = new Filter('strtolower');
		$this->filterChain->append($filter);

		$filterLinks = iterator_to_array($this->filterChain);

		$this->assertSame(
			$filter,
			$filterLinks[0]->getFilter()
		);
	}

	/**
	* @testdox Strings that start with # are kept as-is to indicate the use of built-in filters
	*/
	public function testBuiltIn()
	{
		$this->filterChain->append('#int');

		$filterLinks = iterator_to_array($this->filterChain);

		$this->assertSame(
			'#int',
			$filterLinks[0]->getFilter()
		);
	}

	/**
	* @testdox append() adds the filter at the end of the chain
	* @depends testBuiltIn
	*/
	public function testAppend()
	{
		$this->filterChain->append('#int');
		$this->filterChain->append('#url');

		$filterLinks = iterator_to_array($this->filterChain);

		$this->assertSame(
			'#int',
			$filterLinks[0]->getFilter()
		);

		$this->assertSame(
			'#url',
			$filterLinks[1]->getFilter()
		);
	}

	/**
	* @testdox prepend() adds the filter at the beginning of the chain
	* @depends testBuiltIn
	*/
	public function testPrepend()
	{
		$this->filterChain->prepend('#int');
		$this->filterChain->prepend('#url');

		$filterLinks = iterator_to_array($this->filterChain);

		$this->assertSame(
			'#url',
			$filterLinks[0]->getFilter()
		);

		$this->assertSame(
			'#int',
			$filterLinks[1]->getFilter()
		);
	}

	/**
	* @testdox has() returns false if the given filter is not present in the chain
	* @depends testBuiltIn
	*/
	public function testNegativeHas()
	{
		$this->filterChain->append('#int');

		$this->assertFalse($this->filterChain->has('#url'));
	}

	/**
	* @testdox has() returns true if the given built-in filter is present in the chain
	* @depends testBuiltIn
	*/
	public function testHasBuiltIn()
	{
		$this->filterChain->append('#int');

		$this->assertTrue($this->filterChain->has('#int'));
	}

	/**
	* @testdox has() returns true if the given PHP string callback is present in the chain
	* @depends testBuiltIn
	*/
	public function testHasStringCallback()
	{
		$this->filterChain->append('strtolower');

		$this->assertTrue($this->filterChain->has('strtolower'));
	}
}