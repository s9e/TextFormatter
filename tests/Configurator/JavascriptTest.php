<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Javascript;
use s9e\TextFormatter\Configurator\Javascript\Minifier;
use s9e\TextFormatter\Configurator\Javascript\Minifiers\Noop;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Javascript
*/
class JavascriptTest extends Test
{
	/**
	* @testdox getMinifier() returns an instance of ClosureCompilerService by default
	*/
	public function testGetMinifier()
	{
		$javascript = new Javascript(new Configurator);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Javascript\\Minifiers\\ClosureCompilerService',
			$javascript->getMinifier()
		);
	}

	/**
	* @testdox setMinifier() accepts an object that implements Minifier
	*/
	public function testSetMinifier()
	{
		$javascript = new Javascript(new Configurator);
		$minifier   = new Noop;

		$javascript->setMinifier($minifier);

		$this->assertSame($minifier, $javascript->getMinifier());
	}
}