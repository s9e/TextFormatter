<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\FilterChain
* @covers s9e\TextFormatter\Configurator\Collections\AttributeFilterChain
*/
class AttributeFilterChainTest extends Test
{
	private function privateMethod() {}
	public function doNothing() {}

	/**
	* @testdox append() throws an InvalidArgumentException on invalid callbacks
	*/
	public function testAppendInvalidCallback()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Filter '*invalid*' is neither callable nor an instance of s9e\\TextFormatter\\Configurator\\Items\\AttributeFilter");

		$filterChain = new AttributeFilterChain;
		$filterChain->append('*invalid*');
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on invalid callbacks
	*/
	public function testPrependInvalidCallback()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Filter '*invalid*' is neither callable nor an instance of s9e\\TextFormatter\\Configurator\\Items\\AttributeFilter");

		$filterChain = new AttributeFilterChain;
		$filterChain->prepend('*invalid*');
	}

	/**
	* @testdox append() throws an InvalidArgumentException on uncallable callbacks
	*/
	public function testAppendUncallableCallback()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('is neither callable nor an instance of s9e\\TextFormatter\\Configurator\\Items\\AttributeFilter');

		$filterChain = new AttributeFilterChain;
		$filterChain->append([__CLASS__, 'privateMethod']);
	}

	/**
	* @testdox prepend() throws an InvalidArgumentException on uncallable callbacks
	*/
	public function testPrependUncallableCallback()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('is neither callable nor an instance of s9e\\TextFormatter\\Configurator\\Items\\AttributeFilter');

		$filterChain = new AttributeFilterChain;
		$filterChain->prepend([__CLASS__, 'privateMethod']);
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
			$filterChain->append([$this, 'doNothing'])
		);
	}

	/**
	* @testdox Default filters such as "#int" are normalized to an instance of the corresponding AttributeFilter
	*/
	public function testDefaultFilter()
	{
		$filterChain = new AttributeFilterChain;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\IntFilter',
			$filterChain->append('#int')
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