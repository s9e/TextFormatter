<?php

namespace s9e\TextFormatter\Tests\Renderers;

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
		$this->configurator->setRendererGenerator('PHP');
	}

	/**
	* @testdox Is serializable
	*/
	public function testSerializable()
	{
		$this->configurator->stylesheet->parameters->add('foo', "'bar'");
		$renderer = $this->configurator->getRenderer();
		unset($renderer->source);

		$this->assertEquals(
			$renderer,
			unserialize(serialize($renderer))
		);
	}

	/**
	* @testdox The source of the renderer is omitted for serialization
	*/
	public function testNoSourceSerialize()
	{
		$renderer = $this->configurator->getRenderer();

		$this->assertNotContains('source', serialize($renderer));
	}

	/**
	* @testdox Custom properties are preserved during serialization
	*/
	public function testSerializePreserveCustom()
	{
		$renderer = $this->configurator->getRenderer();
		$renderer->foo = 'bar';

		$renderer = unserialize(serialize($renderer));

		$this->assertObjectHasAttribute('foo', $renderer);
		$this->assertSame('bar', $renderer->foo);
	}
}