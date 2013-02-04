<?php

namespace s9e\TextFormatter\Tests\Renderers;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Stylesheet;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP;
use s9e\TextFormatter\Tests\RendererTests;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Renderer
*/
class PHPTest extends Test
{
	use RendererTests;

	public function setUp()
	{
		$this->configurator->rendererGenerator = new PHP;
	}

	/**
	* @testdox Is serializable
	*/
	public function testSerializable()
	{
		$renderer = $this->configurator->getRenderer();

		$this->assertEquals(
			$renderer,
			unserialize(serialize($renderer))
		);
	}
}