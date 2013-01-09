<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\Noop;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript
*/
class JavaScriptTest extends Test
{
	/**
	* @testdox getMinifier() returns an instance of ClosureCompilerService by default
	*/
	public function testGetMinifier()
	{
		$javascript = new JavaScript(new Configurator);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\ClosureCompilerService',
			$javascript->getMinifier()
		);
	}

	/**
	* @testdox setMinifier() accepts an object that implements Minifier
	*/
	public function testSetMinifier()
	{
		$javascript = new JavaScript(new Configurator);
		$minifier   = new Noop;

		$javascript->setMinifier($minifier);

		$this->assertSame($minifier, $javascript->getMinifier());
	}

	/**
	* 
	*
	* @return void
	*/
	public function test()
	{
		$configurator = new Configurator;
		$configurator->BBCodes->addCustom('[a id={RANDOM=10,30} href={URL}]{TEXT}[/a]', '');

		$configurator->javascript->getParser();
	}
}