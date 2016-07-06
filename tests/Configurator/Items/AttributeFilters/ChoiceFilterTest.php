<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\ChoiceFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\ChoiceFilter
*/
class ChoiceFilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterRegexp()
	*/
	public function testCallback()
	{
		$filter = new ChoiceFilter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterRegexp',
			$filter->getCallback()
		);
	}

	/**
	* @testdox __construct() forwards its arguments to setValues()
	*/
	public function testConstructorArguments()
	{
		$className = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\ChoiceFilter';
		$filter = $this->getMockBuilder($className)
		               ->disableOriginalConstructor()
		               ->getMock();

		$filter->expects($this->once())
		       ->method('setValues')
		       ->with(['one', 'two'], true);

		$filter->__construct(['one', 'two'], true);
	}

	/**
	* @testdox setValues() creates a regexp that matches all given values (case-insensitive) and calls setRegexp()
	*/
	public function testSetValues()
	{
		$filter = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\ChoiceFilter')
		             ->setMethods(['setRegexp'])
		             ->getMock();

		$filter->expects($this->once())
		       ->method('setRegexp')
		       ->with('/^(?>one|two)$/Di');

		$filter->setValues(['one', 'two']);
	}

	/**
	* @testdox setValues() creates a case-sensitive regexp if its second argument is TRUE
	*/
	public function testSetValuesCaseSensitive()
	{
		$filter = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\ChoiceFilter')
		             ->setMethods(['setRegexp'])
		             ->getMock();

		$filter->expects($this->once())
		       ->method('setRegexp')
		       ->with('/^(?>one|two)$/D');

		$filter->setValues(['one', 'two'], true);
	}

	/**
	* @testdox setValues() creates a Unicode-aware regexp if any values are non-ASCII
	*/
	public function testSetValuesUnicode()
	{
		$filter = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\ChoiceFilter')
		             ->setMethods(['setRegexp'])
		             ->getMock();

		$filter->expects($this->once())
		       ->method('setRegexp')
		       ->with('/^(?>pokémon|yugioh)$/Diu');

		$filter->setValues(['pokémon', 'yugioh']);
	}

	/**
	* @testdox setValues() throws an exception if its second argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be a boolean
	*/
	public function testSetValuesInvalidBool()
	{
		$filter = new ChoiceFilter;
		$filter->setValues(['one', 'two'], 'notabool');
	}
}