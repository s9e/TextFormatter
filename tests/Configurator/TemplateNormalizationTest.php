<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateNormalization
*/
class TemplateNormalizationTest extends Test
{
	/**
	* @testdox lowercase() makes an ASCII string lowercase
	*/
	public function testLowercase()
	{
		$this->assertSame('foo-bar3', TemplateNormalization::lowercase('FoO-bAr3'));
	}
}