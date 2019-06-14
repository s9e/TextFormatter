<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators\PHP\XPathConvertor;

use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Runner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Runner
*/
class RunnerTest extends Test
{
	/**
	* @testdox convert() throws an exception if the expression cannot be converted
	*/
	public function testUnsupported()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Cannot convert 'foo()'");

		$runner = new Runner;
		$runner->convert('foo()');
	}

	/**
	* @testdox Convertors can be set in the constructor
	*/
	public function testCustomConvertors()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Cannot convert '1'");

		$runner = new Runner([]);
		$runner->convert('1');
	}
}