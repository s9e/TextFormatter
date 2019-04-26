<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\RulesGeneratorList;
use s9e\TextFormatter\Configurator\RulesGenerators\AutoCloseIfVoid;
use s9e\TextFormatter\Configurator\RulesGenerators\EnforceOptionalEndTags;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\RulesGeneratorList
*/
class RulesGeneratorListTest extends Test
{
	/**
	* @testdox add() normalizes a string into an instance of a class of the same name in s9e\TextFormatter\Configurator\RulesGenerators
	*/
	public function testAddNormalizeValue()
	{
		$collection = new RulesGeneratorList;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\RulesGenerators\\AutoCloseIfVoid',
			$collection->add('AutoCloseIfVoid')
		);
	}

	/**
	* @testdox add() adds BooleanRulesGenerator instances as-is
	*/
	public function testAddInstanceBoolean()
	{
		$collection = new RulesGeneratorList;
		$generator  = new AutoCloseIfVoid;

		$this->assertSame(
			$generator,
			$collection->add($generator)
		);
	}

	/**
	* @testdox add() adds TargetedRulesGenerator instances as-is
	*/
	public function testAddInstanceTargeted()
	{
		$collection = new RulesGeneratorList;
		$generator  = new EnforceOptionalEndTags;

		$this->assertSame(
			$generator,
			$collection->add($generator)
		);
	}

	/**
	* @testdox add() throws an exception on invalid values
	*/
	public function testAddInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid rules generator 'foo'");

		$collection = new RulesGeneratorList;
		$collection->add('foo');
	}
}