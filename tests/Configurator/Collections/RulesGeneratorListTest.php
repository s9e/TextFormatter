<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\RulesGeneratorList;
use s9e\TextFormatter\Configurator\RulesGenerators\AutoCloseIfVoid;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\RulesGeneratorList
*/
class RulesGeneratorListTest extends Test
{
	/**
	* @testdox append() normalizes a string into an instance of a class of the same name in s9e\TextFormatter\Configurator\RulesGenerators
	*/
	public function testAppendNormalizeValue()
	{
		$collection = new RulesGeneratorList;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\RulesGenerators\\AutoCloseIfVoid',
			$collection->append('AutoCloseIfVoid')
		);
	}

	/**
	* @testdox append() adds objects as-is
	*/
	public function testAppendInstance()
	{
		$collection = new RulesGeneratorList;
		$generator  = new AutoCloseIfVoid;

		$this->assertSame(
			$generator,
			$collection->append($generator)
		);
	}
}