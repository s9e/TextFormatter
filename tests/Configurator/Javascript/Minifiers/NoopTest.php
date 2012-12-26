<?php

namespace s9e\TextFormatter\Tests\Configurator\Javascript\Minifiers;

use s9e\TextFormatter\Configurator\Javascript\Minifiers\Noop;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Javascript\Minifiers\Noop
*/
class NoopTest extends Test
{
	/**
	* @testdox minify() returns its first argument
	*/
	public function testNoop()
	{
		$original =
			"function hello(name) {
				alert('Hello, ' + name);
			}
			hello('New user')";

		$expected = $original;

		$minifier = new Noop;
		$this->assertSame($expected, $minifier->minify($original));
	}
}