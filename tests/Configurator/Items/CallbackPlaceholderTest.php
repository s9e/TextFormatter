<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\CallbackPlaceholder;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\CallbackPlaceholder
*/
class CallbackPlaceholderTest extends Test
{
	/**
	* @testdox Is callable
	*/
	public function testIsCallable()
	{
		$cp = new CallbackPlaceholder('');
		$this->assertTrue(is_callable($cp));
	}

	/**
	* @testdox Throws an exception if invoked
	* @expectedException RuntimeException
	* @expectedExceptionMessage CallbackPlaceholder is not meant to be invoked
	*/
	public function testInvoked()
	{
		$cp = new CallbackPlaceholder('');
		$cp();
	}

	/**
	* @testdox asConfig() returns the value passed to the constructor
	*/
	public function testAsConfig()
	{
		$cp = new CallbackPlaceholder('#foo');
		$this->assertSame('#foo', $cp->asConfig());
	}
}