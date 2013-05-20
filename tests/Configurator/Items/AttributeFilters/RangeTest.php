<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Range;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Range
*/
class RangeTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterRange()
	*/
	public function testCallback()
	{
		$filter = new Range;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterRange',
			$filter->getCallback()
		);
	}

	/**
	* @testdox Is safe in CSS
	*/
	public function testIsSafeInCSS()
	{
		$filter = new Range;
		$this->assertTrue($filter->isSafeInCSS());
	}

	/**
	* @testdox Is safe in JS
	*/
	public function testIsSafeInJS()
	{
		$filter = new Range;
		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox Is safe in URL
	*/
	public function testIsSafeInURL()
	{
		$filter = new Range;
		$this->assertTrue($filter->isSafeAsURL());
	}

	/**
	* @testdox __construct() forwards its arguments to setRange()
	*/
	public function testConstructorArguments()
	{
		$filter = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\Range')
		               ->disableOriginalConstructor()
		               ->getMock();

		$filter->expects($this->once())
		       ->method('setRange')
		       ->with(2, 5);

		$filter->__construct(2, 5);
	}

	/**
	* @testdox asConfig() throws an exception if the 'min' var is missing
	* @expectedException RuntimeException
	* @expectedExceptionMessage Range filter is missing a 'min' value
	*/
	public function testMissingMin()
	{
		$filter = new Range;
		$filter->setVars(['max' => 0]);
		$filter->asConfig();
	}

	/**
	* @testdox asConfig() throws an exception if the 'max' var is missing
	* @expectedException RuntimeException
	* @expectedExceptionMessage Range filter is missing a 'max' value
	*/
	public function testMissingMax()
	{
		$filter = new Range;
		$filter->setVars(['min' => 0]);
		$filter->asConfig();
	}

	/**
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$filter = new Range;
		$filter->setRange(1, 5);

		$this->assertInternalType('array', $filter->asConfig());
	}

	/**
	* @testdox setRange() sets the 'min' and 'max' vars
	*/
	public function testSetRange()
	{
		$filter = new Range;
		$filter->setRange(1, 5);

		$this->assertEquals(
			['min' => 1, 'max' => 5],
			$filter->getVars()
		);
	}

	/**
	* @testdox setRange() throws an exception if the first argument is not a number
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Argument 1 passed to s9e\TextFormatter\Configurator\Items\AttributeFilters\Range::setRange must be an integer
	*/
	public function testSetRangeInvalidMin()
	{
		$filter = new Range;
		$filter->setRange('foo', 5);

	}

	/**
	* @testdox setRange() throws an exception if the second argument is not a number
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Argument 2 passed to s9e\TextFormatter\Configurator\Items\AttributeFilters\Range::setRange must be an integer
	*/
	public function testSetRangeInvalidMax()
	{
		$filter = new Range;
		$filter->setRange(1, 'foo');

	}

	/**
	* @testdox setRange() throws an exception if the min value is greater than the max value
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid range
	*/
	public function testSetRangeInvalidRange()
	{
		$filter = new Range;
		$filter->setRange(5, 1);
	}
}