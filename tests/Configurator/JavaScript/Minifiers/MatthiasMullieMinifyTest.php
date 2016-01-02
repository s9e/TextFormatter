<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript\Minifiers;

use s9e\TextFormatter\Configurator\JavaScript\Minifiers\MatthiasMullieMinify;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\Minifiers\MatthiasMullieMinify
*/
class MatthiasMullieMinifyTest extends Test
{
	public function setUp()
	{
		if (!class_exists('MatthiasMullie\\Minify\\JS'))
		{
			$this->markTestSkipped('Requires MatthiasMullie\\Minify\\JS');
		}
	}

	/**
	* @testdox minify() works
	*/
	public function testWorks()
	{
		$minifier = new MatthiasMullieMinify;
		$this->assertSame('alert(1)', $minifier->minify('alert(1);'));
	}
}