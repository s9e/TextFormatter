<?php

namespace s9e\TextFormatter\Tests\Renderers;

use DOMNode;
use ReflectionObject;
use s9e\TextFormatter\Renderers\PHP;
use s9e\TextFormatter\Tests\RendererTests;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Renderer
* @covers s9e\TextFormatter\Renderers\PHP
*/
class PHPTest extends Test
{
	use RendererTests;

	protected function setUp(): void
	{
		$this->configurator->rendering->engine = 'PHP';
	}

	protected function tearDown(): void
	{
		array_map('unlink', glob(sys_get_temp_dir() . '/Renderer_*.php'));
	}

	/**
	* @testdox Is serializable
	*/
	public function testSerializable()
	{
		$this->configurator->rendering->parameters->add('foo', "'bar'");
		$renderer = $this->configurator->rendering->getRenderer();

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
		$renderer = $this->configurator->rendering->getRenderer();

		$this->assertStringNotContainsString('source', serialize($renderer));
	}

	/**
	* @testdox The last output of the renderer is omitted for serialization
	*/
	public function testNoOutputSerialize()
	{
		$renderer = $this->configurator->rendering->getRenderer();
		$renderer->render('<r>xxx</r>');

		$this->assertStringNotContainsString('out', serialize($renderer));
	}

	/**
	* @testdox Internal objects and resources are unset after rendering
	*/
	public function testResourcesUnset()
	{
		// Create a template that requires XPath
		$this->configurator->tags->add('FOO')->template = '<xsl:value-of select="lang()"/>';

		$renderer = $this->configurator->rendering->getRenderer();
		$renderer->render('<r>xxx</r>');

		$reflection = new ReflectionObject($renderer);
		foreach ($reflection->getProperties() as $prop)
		{
			if ($prop->isStatic())
			{
				continue;
			}
			$prop->setAccessible(true);

			$this->assertIsNotObject($prop->getValue($renderer));
			$this->assertIsNotResource($prop->getValue($renderer));
		}
	}

	/**
	* @testdox The abstract renderer has a default implementation for renderQuickTemplate()
	*/
	public function testDefaultQuickTemplate()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Not implemented');

		$renderer = new DummyRenderer;
		$renderer->callRenderQuickTemplate();
	}

	/**
	* @testdox render() throws an exception on invalid XML with a "r" root tag that could be rendered by the Quick renderer
	*/
	public function testInvalidXMLQuick()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Cannot load XML');

		$this->configurator->rendering->getRenderer()->render('<r>');
	}
}

class DummyRenderer extends PHP
{
	public function callRenderQuickTemplate()
	{
		$this->renderQuickTemplate('x', '<x>');
	}

	protected function renderNode(DOMNode $node)
	{
	}
}