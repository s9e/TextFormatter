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
		$js   = 'alert("ok")';
		$code = new Code($js);

		$this->assertSame($js, (string) $code);
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

	/**
	* @testdox filterConfig('PHP') returns null
	*/
	public function testFilterConfigPHP()
	{
		$js   = 'alert("ok")';
		$code = new Code($js);

		$this->assertNull($code->filterConfig('PHP'));
	}

	/**
	* @testdox filterConfig('PHP') returns the Code instance
	*/
	public function testFilterConfigJS()
	{
		$js   = 'alert("ok")';
		$code = new Code($js);

		$this->assertSame($code, $code->filterConfig('JS'));
	}
}