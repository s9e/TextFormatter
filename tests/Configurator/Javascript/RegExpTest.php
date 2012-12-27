<?php

namespace s9e\TextFormatter\Tests\Configurator\Javascript;

use s9e\TextFormatter\Configurator\Javascript\RegExp;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Javascript\RegExp
*/
class RegExpTest extends Test
{
	/**
	* @testdox Returns a Javascript literal when cast as a string
	*/
	public function testAsString()
	{
		$this->assertEquals(
			'/foo/i',
			new RegExp('foo', 'i')
		);
	}
}