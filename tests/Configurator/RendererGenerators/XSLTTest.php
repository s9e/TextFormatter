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
	* @testdox Calls $optimizer->optimizeTemplate() for each template
	*/
	public function testOptimizerCalls()
	{
		$mock = $this->getMockBuilder('stdClass')
		             ->setMethods(['optimizeTemplate'])
		             ->getMock();
		$mock->expects($this->at(0))
		     ->method('optimizeTemplate')
		     ->with('<b>X</b>')
		     ->will($this->returnValue('<b>x</b>'));
		$mock->expects($this->at(1))
		     ->method('optimizeTemplate')
		     ->with('<b>Y</b>')
		     ->will($this->returnValue('<b>y</b>'));

		$this->configurator->rendering->engine->optimizer = $mock;
		$this->configurator->tags->add('X')->template = '<b>X</b>';
		$this->configurator->tags->add('Y')->template = '<b>Y</b>';

		$xsl = $this->getXSL();

		$this->assertContains('<xsl:template match="X"><b>x</b></xsl:template>', $xsl);
		$this->assertContains('<xsl:template match="Y"><b>y</b></xsl:template>', $xsl);
	}
}