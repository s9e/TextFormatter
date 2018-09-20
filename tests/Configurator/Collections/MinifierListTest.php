<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\MinifierList;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\MinifierList
*/
class MinifierListTest extends Test
{
	/**
	* @testdox add() normalizes minifier names to instances of s9e\TextFormatter\Configurator\JavaScript\Minifiers if applicable
	*/
	public function testAddNormalizeValueInstanceOf()
	{
		$collection = new MinifierList;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\Noop',
			$collection->add('Noop')
		);
	}

	/**
	* @testdox add() throws an exception when the value is neither a Minifier instance nor a known minifier
	* @expectedException InvalidArgumentException Nope
	*/
	public function testAddNormalizeValueFail()
	{
		$collection = new MinifierList;
		$collection->add('Nope');
	}

	/**
	* @testdox add() throws an exception when the value is a boolean
	* @expectedException InvalidArgumentException false
	*/
	public function testAddNormalizeValueBool()
	{
		$collection = new MinifierList;
		$collection->add(false);
	}

	/**
	* @testdox add() accepts an array
	*/
	public function testAddNormalizeValueArray()
	{
		$collection = new MinifierList;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\Noop',
			$collection->add(['Noop'])
		);
	}

	/**
	* @testdox add() accepts an array that contains the minifier's name followed by any number of arguments to be passed to the constructor
	*/
	public function testAddNormalizeValueArrayArguments()
	{
		$collection = new MinifierList;
		$command    = 'npx google-closure-compiler';
		$minifier   = $collection->add(['ClosureCompilerApplication', $command]);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\ClosureCompilerApplication',
			$minifier
		);
		$this->assertSame($command, $minifier->command);
	}
}