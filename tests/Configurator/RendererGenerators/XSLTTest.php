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
	* @testdox Sets the output method to "xml" if the rendering type is "xhtml"
	*/
	public function testOutputMethodXML()
	{
		$this->configurator->rendering->type = 'xhtml';

		$this->assertContains(' method="xml"', $this->getXSL());
	}

	/**
	* @testdox Sets the stylesheet's output to omit the XML declaration if rendering type is "xhtml"
	*/
	public function testOutputMethodProlog()
	{
		$this->configurator->rendering->type = 'xhtml';

		$this->assertContains(' omit-xml-declaration="yes"', $this->getXSL());
	}

	/**
	* @testdox Merges duplicate templates
	*/
	public function testMergesDuplicateTemplates()
	{
		$this->configurator->tags->add('X')->template = 'X';
		$this->configurator->tags->add('Y')->template = 'X';

		$this->assertContains(
			'<xsl:template match="X|Y">X</xsl:template>',
			$this->getXSL()
		);
	}

	/**
	* @testdox Represents empty templates with a self-closing element
	*/
	public function testEmptyTemplates()
	{
		$this->assertContains(
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

		$this->assertContains(
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

		$this->assertContains(
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

		$this->assertContains(
			'<xsl:param name="foo"',
			$this->getXSL()
		);

		$this->assertContains(
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

		$this->assertContains(
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

		$this->assertContains(
			'<xsl:param name="foo">\'&quot;&amp;&lt;&gt;</xsl:param>',
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

		$this->assertContains(
			'<xsl:template match="B|I|U|p"><xsl:element name="{translate(name(),\'BIU\',\'biu\')}"><xsl:apply-templates/></xsl:element></xsl:template>',
			$xsl
		);

		foreach ($this->configurator->tags as $tag)
		{
			$this->assertNotContains((string) $tag->template, $xsl);
		}
	}

	/**
	* @testdox Does not attempt to merge simple templates together if there's only one user-defined simple tag
	*/
	public function testSimpleTemplatesNot()
	{
		$this->configurator->tags->add('B')->template = '<b><xsl:apply-templates/></b>';

		$xsl = $this->getXSL();

		$this->assertContains('<b><xsl:apply-templates/></b>', $xsl);
		$this->assertNotContains('<xsl:element>', $xsl);
	}

	/**
	* @testdox The merged simple template handles namespaced tags
	*/
	public function testSimpleTemplatesNamespaced()
	{
		$this->configurator->tags->add('B')->template      = '<b><xsl:apply-templates/></b>';
		$this->configurator->tags->add('html:b')->template = '<b><xsl:apply-templates/></b>';
		$this->configurator->tags->add('html:i')->template = '<i><xsl:apply-templates/></i>';

		$xsl = $this->getXSL();

		$this->assertContains(
			'<xsl:template match="B|html:b|html:i|p"><xsl:element name="{translate(local-name(),\'B\',\'b\')}"><xsl:apply-templates/></xsl:element></xsl:template>',
			$xsl
		);
	}

	/**
	* @testdox The merged simple template handles namespaced tags that don't need to be lowercased
	*/
	public function testSimpleTemplatesNamespacedLowercased()
	{
		$this->configurator->tags->add('html:b')->template = '<b><xsl:apply-templates/></b>';
		$this->configurator->tags->add('html:i')->template = '<i><xsl:apply-templates/></i>';

		$xsl = $this->getXSL();

		$this->assertContains(
			'<xsl:template match="html:b|html:i|p"><xsl:element name="{local-name()}"><xsl:apply-templates/></xsl:element></xsl:template>',
			$xsl
		);
	}
}