<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript;

use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\RegExp
*/
class RegExpTest extends Test
{
	/**
	* @testdox Returns a JavaScript literal when cast as a string
	*/
	public function testAsString()
	{
		$this->assertEquals(
			'/foo/i',
			new RegExp('foo', 'i')
		);
	}

	/**
	* @testdox The default map as an empty entry that represents capture #0
	*/
	public function testDefaultMap()
	{
		$regexp = new RegExp('foo');
		$this->assertSame([''], $regexp->map);
	}
}