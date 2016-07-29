<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript;

use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\Code
*/
class CodeTest extends Test
{
	/**
	* @testdox Can be cast as a string
	*/
	public function testAsString()
	{
		$this->assertSame(
			'alert("ok")',
			(string) new Code('alert("ok")')
		);
	}

	/**
	* @testdox __toString() always returns a string
	*/
	public function testAsStringType()
	{
		$this->assertSame(
			'42',
			(string) new Code(42)
		);
	}
}