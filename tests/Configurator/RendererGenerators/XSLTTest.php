<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators;

use s9e\TextFormatter\Configurator\RendererGenerators\XSLT;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension xsl
* @covers s9e\TextFormatter\Configurator\RendererGenerators\XSLT
*/
class XSLTTest extends Test
{
	/**
	* @testdox Returns an instance of Renderer
	*/
	public function testInstance()
	{
		$generator = new XSLT;
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Renderer',
			$generator->getRenderer($this->configurator->stylesheet)
		);
	}
}