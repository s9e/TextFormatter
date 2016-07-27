<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Rendering
*/
class RenderingTest extends Test
{
	/**
	* @testdox Default engine is XSLT
	*/
	public function testDefaultEngineXSLT()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\RendererGenerators\\XSLT',
			$this->configurator->rendering->engine
		);
	}

	/**
	* @testdox setEngine('PHP') sets the engine to a new instance of s9e\TextFormatter\Configurator\RendererGenerators\PHP
	*/
	public function testSetEngine()
	{
		$this->configurator->rendering->setEngine('PHP');
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\RendererGenerators\\PHP',
			$this->configurator->rendering->engine
		);
	}

	/**
	* @testdox setEngine('PHP', '/tmp') creates a new instance of s9e\TextFormatter\Configurator\RendererGenerators\PHP passing '/tmp' to its constructor
	*/
	public function testGetRendererArgs()
	{
		$this->configurator->rendering->setEngine('PHP', '/tmp');

		$this->assertAttributeSame(
			'/tmp',
			'cacheDir',
			$this->configurator->rendering->engine
		);
	}

	/**
	* @testdox setEngine() returns the new instance of RendererGenerator
	*/
	public function testSetEngineReturn()
	{
		$return = $this->configurator->rendering->setEngine('PHP');

		$this->assertSame(
			$this->configurator->rendering->engine,
			$return
		);
	}

	/**
	* @testdox getRenderer() invokes $this->engine->getRenderer()
	*/
	public function testGetRendererInvokesGenerator()
	{
		$mock = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\RendererGenerators\\XSLT')
		             ->disableOriginalConstructor()
		             ->getMock();
		$mock->expects($this->once())
		     ->method('getRenderer')
		     ->with($this->configurator->rendering);

		$this->configurator->rendering->engine = $mock;
		$this->configurator->rendering->getRenderer();
	}

	/**
	* @testdox getAllParameters() returns parameters that were formally defined
	*/
	public function testGetAllParametersDefined()
	{
		$this->configurator->rendering->parameters['foo'] = 'bar';
		$this->configurator->rendering->parameters['bar'] = 'baz';

		$this->assertEquals(
			['foo' => 'bar', 'bar' => 'baz'],
			$this->configurator->rendering->getAllParameters()
		);
	}

	/**
	* @testdox getAllParameters() returns parameters that are inferred from the templates
	*/
	public function testGetAllParametersInferred()
	{
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';

		$this->assertEquals(
			['foo' => ''],
			$this->configurator->rendering->getAllParameters()
		);
	}

	/**
	* @testdox getAllParameters() returns parameters that were formally defined over those that are inferred from the templates
	*/
	public function testGetAllParametersPrecedence()
	{
		$this->configurator->rendering->parameters['foo'] = 'bar';
		$this->configurator->tags->add('X')->template = '<xsl:value-of select="$foo"/>';

		$this->assertEquals(
			['foo' => 'bar'],
			$this->configurator->rendering->getAllParameters()
		);
	}

	/**
	* @testdox getTemplates() returns default templates for br and p and empty templates for i, e, s
	*/
	public function testGetTemplatesDefault()
	{
		$this->assertEquals(
			[
				'br' => '<br/>',
				'e'  => '',
				'i'  => '',
				'p'  => '<p><xsl:apply-templates/></p>',
				's'  => ''
			],
			$this->configurator->rendering->getTemplates()
		);
	}

	/**
	* @testdox getTemplates() returns templates gathered from tags
	*/
	public function testGetTemplatesTags()
	{
		$this->configurator->tags->add('FOO')->template = 'foo';
		$this->configurator->tags->add('BAR')->template = 'bar';
		$templates = $this->configurator->rendering->getTemplates();

		$this->assertArrayHasKey('FOO', $templates);
		$this->assertSame($templates['FOO'], 'foo');
		$this->assertArrayHasKey('BAR', $templates);
		$this->assertSame($templates['BAR'], 'bar');
	}
}