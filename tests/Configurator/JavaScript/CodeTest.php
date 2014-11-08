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
		$this->assertEquals(
			'alert("ok")',
			new Code('alert("ok")')
		);
	}
}