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
			$generator->getRenderer($this->configurator->stylesheet)
		);
	}

	/**
	* @testdox Configures the renderer for HTML output if applicable
	*/
	public function testOutputHTML()
	{
		$this->configurator->stylesheet->outputMethod = 'html';
		$generator = new Unformatted;

		$this->assertAttributeSame(
			true,
			'htmlOutput',
			$generator->getRenderer($this->configurator->stylesheet)
		);
	}

	/**
	* @testdox Configures the renderer for XML output if applicable
	*/
	public function testOutputXML()
	{
		$this->configurator->stylesheet->outputMethod = 'xml';
		$generator = new Unformatted;

		$this->assertAttributeSame(
			false,
			'htmlOutput',
			$generator->getRenderer($this->configurator->stylesheet)
		);
	}
}