<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\FilterChain
* @covers s9e\TextFormatter\Configurator\Collections\AttributeFilterChain
* @covers s9e\TextFormatter\Configurator\Helpers\FilterHelper
*/
class AttributeFilterChainTest extends Test
{
	private function privateMethod() {}
	public function doNothing() {}

	/**
	* @testdox append() throws a RuntimeException on invalid callbacks
	*/
	public function testAppendInvalidCallback()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Cannot parse '*invalid*'");

		$filterChain = new AttributeFilterChain;
		$filterChain->append('*invalid*');
	}

	/**
	* @testdox prepend() throws a RuntimeException on invalid callbacks
	*/
	public function testPrependInvalidCallback()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Cannot parse '*invalid*'");

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
	* @testdox Default filters accept positional constructor arguments
	*/
	public function testDefaultFilterPositionalConstructorArguments()
	{
		$filterChain = new AttributeFilterChain;
		$filter      = $filterChain->append('#range(1, 12)');

		$this->assertEquals(['min' => 1, 'max' => 12], $filter->getVars());
	}

	/**
	* @testdox Default filters accept named constructor arguments
	*/
	public function testDefaultFilterNamedConstructorArguments()
	{
		$filterChain = new AttributeFilterChain;
		$filter      = $filterChain->append('#range(max: 12, min: 3)');

		$this->assertEquals(['min' => 3, 'max' => 12], $filter->getVars());
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

	/**
	* @testdox Automatically parses callback parameters
	*/
	public function testAttributeFilterParams()
	{
		$filterChain = new AttributeFilterChain;
		$actual      = $filterChain->append('str_replace($attrValue, "foo", "bar")');
		$expected    = new AttributeFilter('str_replace');
		$expected->resetParameters();
		$expected->addParameterByName('attrValue');
		$expected->addParameterByValue('foo');
		$expected->addParameterByValue('bar');

		$this->assertEquals($expected, $actual);
	}
}