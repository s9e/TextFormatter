<?php

namespace s9e\TextFormatter\Tests\Renderers;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Stylesheet;
use s9e\TextFormatter\Renderers\XSLT;
use s9e\TextFormatter\Tests\RendererTests;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Renderer
* @covers s9e\TextFormatter\Renderers\XSLT
*/
class XSLTTest extends Test
{
	use RendererTests;

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


	/**
	* @testdox Renders multi-line text in HTML after un/serialization
	*/
	public function testUnserializedMultiLineTextHTML()
	{
		$xml = '<pt>One<br/>two</pt>';

		$this->configurator->stylesheet->setOutputMethod('html');
		$renderer = unserialize(serialize($this->configurator->getRenderer()));

		$this->assertSame(
			'One<br>two',
			$renderer->render($xml)
		);
	}

	/**
	* @testdox Renders multi-line text in XHTML after un/serialization
	*/
	public function testUnserializedMultiLineTextXHTML()
	{
		$xml = '<pt>One<br/>two</pt>';

		$this->configurator->stylesheet->setOutputMethod('xml');
		$renderer = unserialize(serialize($this->configurator->getRenderer()));

		$this->assertSame(
			'One<br/>two',
			$renderer->render($xml)
		);
	}
}