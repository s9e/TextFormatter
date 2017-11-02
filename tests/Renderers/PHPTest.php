<?php

namespace s9e\TextFormatter\Tests\Renderers;

use ReflectionObject;
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
		$this->configurator->rendering->engine = 'PHP';
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

		$this->assertNotContains('source', serialize($renderer));
	}

	/**
	* @testdox The last output of the renderer is omitted for serialization
	*/
	public function testNoOutputSerialize()
	{
		$renderer = $this->configurator->rendering->getRenderer();
		$renderer->render('<r>xxx</r>');

		$this->assertNotContains('out', serialize($renderer));
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

			$this->assertAttributeNotInternalType(
				'object',
				$prop->getName(),
				$renderer
			);
			$this->assertAttributeNotInternalType(
				'resource',
				$prop->getName(),
				$renderer
			);
		}
	}
}