<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\RangeFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\RangeFilter
*/
class RangeFilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\AttributeFilters\NumericFilter::filterRange()
	*/
	public function testCallback()
	{
		$filter = new RangeFilter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\NumericFilter::filterRange',
			$filter->getCallback()
		);
	}

	/**
	* @testdox Is safe in CSS
	*/
	public function testIsSafeInCSS()
	{
		$filter = new RangeFilter;
		$this->assertTrue($filter->isSafeInCSS());
	}

	/**
	* @testdox Is safe in JS
	*/
	public function testIsSafeInJS()
	{
		$filter = new RangeFilter;
		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox Is safe in URL
	*/
	public function testIsSafeInURL()
	{
		$filter = new RangeFilter;
		$this->assertTrue($filter->isSafeAsURL());
	}

	/**
	* @testdox __construct() forwards its arguments to setRange()
	*/
	public function testConstructorArguments()
	{
		$className = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\RangeFilter';
		$filter = $this->getMockBuilder($className)
		               ->disableOriginalConstructor()
		               ->getMock();

		$filter->expects($this->once())
		       ->method('setRange')
		       ->with(2, 5);

		$filter->__construct(2, 5);
	}

	/**
	* @testdox asConfig() throws an exception if the 'min' var is missing
	*/
	public function testMissingMin()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Range filter is missing a 'min' value");

		$filter = new RangeFilter;
		$filter->setVars(['max' => 0]);
		$filter->asConfig();
	}

	/**
	* @testdox asConfig() throws an exception if the 'max' var is missing
	*/
	public function testMissingMax()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Range filter is missing a 'max' value");

		$filter = new RangeFilter;
		$filter->setVars(['min' => 0]);
		$filter->asConfig();
	}

	/**
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$filter = new RangeFilter;
		$filter->setRange(1, 5);

		$this->assertIsArray($filter->asConfig());
	}

	/**
	* @testdox setRange() sets the 'min' and 'max' vars
	*/
	public function testSetRange()
	{
		$filter = new RangeFilter;
		$filter->setRange(1, 5);

		$this->assertEquals(
			['min' => 1, 'max' => 5],
			$filter->getVars()
		);
	}

	/**
	* @testdox setRange() throws an exception if the first argument is not a number
	*/
	public function testSetRangeInvalidMin()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Argument 1 passed to s9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\RangeFilter::setRange must be an integer');

		$filter = new RangeFilter;
		$filter->setRange('foo', 5);

	}

	/**
	* @testdox setRange() throws an exception if the second argument is not a number
	*/
	public function testSetRangeInvalidMax()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Argument 2 passed to s9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\RangeFilter::setRange must be an integer');

		$filter = new RangeFilter;
		$filter->setRange(1, 'foo');

	}

	/**
	* @testdox setRange() throws an exception if the min value is greater than the max value
	*/
	public function testSetRangeInvalidRange()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Invalid range');

		$filter = new RangeFilter;
		$filter->setRange(5, 1);
	}
}