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
	protected function getXSL()
	{
		return $this->configurator->rendering->engine->getXSL($this->configurator->rendering);
	}

	/**
	* @testdox Returns an instance of Renderer
	*/
	public function testInstance()
	{
		$generator = new XSLT;
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Renderer',
			$generator->getRenderer($this->configurator->rendering)
		);
	}

	/**
	* @testdox Merges duplicate templates
	*/
	public function testMergesDuplicateTemplates()
	{
		$this->configurator->tags->add('X')->template = 'X';
		$this->configurator->tags->add('Y')->template = 'X';

		$this->assertStringContainsString(
			'<xsl:template match="X|Y">X</xsl:template>',
			$this->getXSL()
		);
	}

	/**
	* @testdox Represents empty templates with a self-closing element
	*/
	public function testEmptyTemplates()
	{
		$this->assertStringContainsString(
			'<xsl:template match="e|i|s"/>',
			$this->getXSL()
		);
	}

	/**
	* @testdox Generates the namespace declarations necessary for prefixed tags
	*/
	public function testDeclaresNamespaces()
	{
		$this->configurator->tags->add('X:A')->template = 'X';
		$this->configurator->tags->add('Y:B')->template = 'Y';

		$this->assertStringContainsString(
			'xmlns:X="urn:s9e:TextFormatter:X" xmlns:Y="urn:s9e:TextFormatter:Y"',
			$this->getXSL()
		);
	}

	/**
	* @testdox Generates an exclude-result-prefixes directive for all the declared prefixes
	*/
	public function testExcludesPrefixes()
	{
		$this->configurator->tags->add('X:A')->template = 'X';
		$this->configurator->tags->add('Y:B')->template = 'Y';

		$this->assertStringContainsString(
			'exclude-result-prefixes="X Y"',
			$this->getXSL()
		);
	}

	/**
	* @testdox Includes parameters in the stylesheet
	*/
	public function testParameters()
	{
		$this->configurator->rendering->parameters->add('foo');
		$this->configurator->rendering->parameters->add('bar');

		$this->assertStringContainsString(
			'<xsl:param name="foo"',
			$this->getXSL()
		);

		$this->assertStringContainsString(
			'<xsl:param name="bar"',
			$this->getXSL()
		);
	}

	/**
	* @testdox Includes a parameter's default value in the stylesheet
	*/
	public function testParameterValue()
	{
		$this->configurator->rendering->parameters->add('foo', 'bar');

		$this->assertStringContainsString(
			'<xsl:param name="foo">bar</xsl:param>',
			$this->getXSL()
		);
	}

	/**
	* @testdox escapes a parameter's default value
	*/
	public function testParameterValueEscaped()
	{
		$this->configurator->rendering->parameters->add('foo', '\'"&<>');

		$this->assertStringContainsString(
			'<xsl:param name="foo">\'"&amp;&lt;&gt;</xsl:param>',
			$this->getXSL()
		);
	}

	/**
	* @testdox Merges simple templates together
	*/
	public function testSimpleTemplates()
	{
		$this->configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';
		$this->configurator->tags->add('I')->template = '<i><xsl:apply-templates/></i>';
		$this->configurator->tags->add('U')->template = '<u><xsl:apply-templates/></u>';

		$xsl = $this->getXSL();

		$this->assertStringContainsString(
			'<xsl:template match="B|I|U|p"><xsl:element name="{translate(name(),\'BIU\',\'biu\')}"><xsl:apply-templates/></xsl:element></xsl:template>',
			$xsl
		);

		foreach ($this->configurator->tags as $tag)
		{
			$this->assertStringNotContainsString((string) $tag->template, $xsl);
		}
	}

	/**
	* @testdox Optimizes each template
	*/
	public function testOptimizesAll()
	{
		$this->configurator->tags->add('X')->template = '<b data-s9e-livepreview-postprocess=""/>';
		$this->configurator->tags->add('Y')->template = '<i data-s9e-livepreview-postprocess=""/>';

		$xsl = $this->getXSL();

		$this->assertStringContainsString('<xsl:template match="X"><b/></xsl:template>', $xsl);
		$this->assertStringContainsString('<xsl:template match="Y"><i/></xsl:template>', $xsl);
	}

	/**
	* @testdox Removes live preview attributes
	*/
	public function testRemovesLivePreviewAttributes()
	{
		$this->configurator->tags->add('X')->template = '<hr data-s9e-livepreview-ignore-attrs="foo"/>';

		$this->assertStringNotContainsString('livepreview', $this->getXSL());
	}
}