<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\AttributeFilterChain
*/
class AttributeFilterChainTest extends Test
{
	private function privateMethod() {}
	public function doNothing() {}

	/**
	* @testdox append() throws an InvalidArgumentException on invalid callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Filter '*invalid*' is neither callable nor an instance of AttributeFilter
	*/
	public function testAppendInvalidCallback()
	{
		$filterChain = new AttributeFilterChain;
		$filterChain->append('*invalid*');
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on invalid callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Filter '*invalid*' is neither callable nor an instance of AttributeFilter
	*/
	public function testPrependInvalidCallback()
	{
		$filterChain = new AttributeFilterChain;
		$filterChain->prepend('*invalid*');
	}

	/**
	* @testdox append() throws an InvalidArgumentException on uncallable callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage is neither callable nor an instance of AttributeFilter
	*/
	public function testAppendUncallableCallback()
	{
		$filterChain = new AttributeFilterChain;
		$filterChain->append(array(__CLASS__, 'privateMethod'));
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on uncallable callbacks 
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage is neither callable nor an instance of AttributeFilter
	*/
	public function testPrependUncallableCallback()
	{
		$filterChain = new AttributeFilterChain;
		$filterChain->prepend(array(__CLASS__, 'privateMethod'));
	}

	/**
	* @testdox PHP string callbacks are normalized to an instance of AttributeFilter
	*/
	public function testStringCallback()
	{
		$filterChain = new AttributeFilterChain;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\AttributeFilter',
			$filterChain->append('strtolower')
		);
	}

	/**
	* @testdox PHP array callbacks are normalized to an instance of AttributeFilter
	*/
	public function testArrayCallback()
	{
		$filterChain = new AttributeFilterChain;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\AttributeFilter',
			$filterChain->append(array($this, 'doNothing'))
		);
	}

	/**
	* @testdox Instances of AttributeFilter are added as-is
	*/
	public function testAttributeFilterInstance()
	{
		$filterChain = new AttributeFilterChain;
		$filter = new AttributeFilter('strtolower');

		$this->assertSame(
			$filter,
			$filterChain->append($filter)
		);
	}
}