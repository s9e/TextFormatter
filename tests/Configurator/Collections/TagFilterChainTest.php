<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\TagFilterChain;
use s9e\TextFormatter\Configurator\Items\TagFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\TagFilterChain
*/
class TagFilterChainTest extends Test
{
	private function privateMethod() {}
	public function doNothing() {}

	/**
	* @testdox append() throws an InvalidArgumentException on invalid callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Filter '*invalid*' is neither callable nor an instance of TagFilter
	*/
	public function testAppendInvalidCallback()
	{
		$filterChain = new TagFilterChain;
		$filterChain->append('*invalid*');
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on invalid callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Filter '*invalid*' is neither callable nor an instance of TagFilter
	*/
	public function testPrependInvalidCallback()
	{
		$filterChain = new TagFilterChain;
		$filterChain->prepend('*invalid*');
	}

	/**
	* @testdox append() throws an InvalidArgumentException on uncallable callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage is neither callable nor an instance of TagFilter
	*/
	public function testAppendUncallableCallback()
	{
		$filterChain = new TagFilterChain;
		$filterChain->append([__CLASS__, 'privateMethod']);
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on uncallable callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage is neither callable nor an instance of TagFilter
	*/
	public function testPrependUncallableCallback()
	{
		$filterChain = new TagFilterChain;
		$filterChain->prepend([__CLASS__, 'privateMethod']);
	}

	/**
	* @testdox PHP string callbacks are normalized to an instance of TagFilter
	*/
	public function testStringCallback()
	{
		$filterChain = new TagFilterChain;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\TagFilter',
			$filterChain->append('strtolower')
		);
	}

	/**
	* @testdox PHP array callbacks are normalized to an instance of TagFilter
	*/
	public function testArrayCallback()
	{
		$filterChain = new TagFilterChain;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\TagFilter',
			$filterChain->append([$this, 'doNothing'])
		);
	}

	/**
	* @testdox Instances of TagFilter are added as-is
	*/
	public function testTagFilterInstance()
	{
		$filterChain = new TagFilterChain;
		$filter = new TagFilter('strtolower');

		$this->assertSame(
			$filter,
			$filterChain->append($filter)
		);
	}
}