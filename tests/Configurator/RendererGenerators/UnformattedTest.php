<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators;

use s9e\TextFormatter\Configurator\RendererGenerators\Unformatted;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RendererGenerators\Unformatted
*/
class UnformattedTest extends Test
{
	/**
	* @testdox Returns an instance of Renderer
	*/
	public function testInstance()
	{
		$generator = new Unformatted;
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Renderer',
			$generator->getRenderer($this->configurator->rendering)
		);
	}
}