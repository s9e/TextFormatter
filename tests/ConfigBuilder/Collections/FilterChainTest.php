<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\Collections\FilterChain;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\FilterChain
*/
class FilterChainTest extends Test
{
	public $filterChain;

	public function setUp()
	{
		$this->filterChain = new FilterChain(array());
	}

	public function doNothing() {}

	/**
	* @testdox append() throws an InvalidArgumentException on invalid callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback '*invalid*' is not callable
	*/
	public function testAppendInvalidCallback()
	{
		$this->filterChain->append('*invalid*');
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on invalid callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback '*invalid*' is not callable
	*/
	public function testPrependInvalidCallback()
	{
		$this->filterChain->prepend('*invalid*');
	}

	/**
	* @testdox append() throws an InvalidArgumentException on uncallable callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback '*invalid*' is not callable
	*/
	public function testAppendUncallableCallback()
	{
		$this->filterChain->append('*invalid*');
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on uncallable callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback '*invalid*' is not callable
	*/
	public function testPrependUncallableCallback()
	{
		$this->filterChain->prepend('*invalid*');
	}

	/**
	* @testdox PHP string callbacks are normalized to an instance of s9e\TextFormatter\ConfigBuilder\Items\Filter
	*/
	public function testStringCallback()
	{
		$this->filterChain->append(array($this, 'doNothing'));

		$filters = iterator_to_array($this->filterChain);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\Filter',
			$filters[0]
		);
	}

	/**
	* @testdox PHP array callbacks are normalized to an instance of s9e\TextFormatter\ConfigBuilder\Items\Filter
	*/
	public function testArrayCallback()
	{
		$this->filterChain->append('strtolower');

		$filters = iterator_to_array($this->filterChain);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\Filter',
			$filters[0]
		);
	}
}