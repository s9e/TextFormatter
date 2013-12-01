<?php

namespace s9e\TextFormatter\Tests\Configurator;

use DOMDocument;
use DOMXPath;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\Stylesheet;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Stylesheet
*/
class StylesheetTest extends Test
{
	/**
	* @testdox setOutputMethod('xml') sets the stylesheet's output method to 'xml'
	*/
	public function testSetOutputMethod()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->setOutputMethod('xml');

		$this->assertContains(' method="xml"', $configurator->stylesheet->get());
	}

	/**
	* @testdox setOutputMethod('xml') sets the stylesheet's output to omit the XML declaration
	*/
	public function testSetOutputMethodProlog()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->setOutputMethod('xml');

		$this->assertContains(' omit-xml-declaration="yes"', $configurator->stylesheet->get());
	}

	/**
	* @testdox setOutputMethod('text') throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Only html and xml methods are supported
	*/
	public function testSetOutputMethodInvalid()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->setOutputMethod('text');
	}

	/**
	* @testdox setWildcardTemplate() accepts a string as template
	*/
	public function testSetWildcardTemplateString()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->setWildcardTemplate('foo', 'FOO');
	}

	/**
	* @testdox setWildcardTemplate() accepts an instance of Template as template
	*/
	public function testSetWildcardTemplateInstance()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->setWildcardTemplate('foo', new Template(''));
	}

	/**
	* @testdox setWildcardTemplate() sets a template with a * matching rule for given prefix
	*/
	public function testSetWildcardTemplate()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->setWildcardTemplate('foo', 'FOO');

		$this->assertContains(
			'<xsl:template match="foo:*">FOO</xsl:template>',
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox setWildcardTemplate() throws an exception if the prefix is empty
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid prefix ''
	*/
	public function testSetWildcardTemplateEmpty()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->setWildcardTemplate('', 'FOO');
	}

	/**
	* @testdox setWildcardTemplate() throws an exception if the prefix is invalid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid prefix '*invalid*'
	*/
	public function testSetWildcardTemplateInvalid()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->setWildcardTemplate('*invalid*', 'FOO');
	}

	/**
	* @testdox get() tests the wildcard templates' safeness
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException escaping
	*/
	public function testGetUnsafeWildcard()
	{
		$configurator = new Configurator;
		$configurator->tags->add('foo:X')->attributes->add('bar');

		$configurator->stylesheet->setWildcardTemplate('foo', '<b disable-output-escaping="yes"><xsl:apply-templates/></b>');

		$configurator->stylesheet->get();
	}

	/**
	* @testdox get() only tests the wildcard templates' safeness against tags in its namespace
	*/
	public function testGetUnsafeWildcardWrongPrefix()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->attributes->add('bar');
		$configurator->tags->add('bar:X')->attributes->add('bar');

		$configurator->stylesheet->setWildcardTemplate('foo', '<a href="{@bar}">_</a>');
		$configurator->stylesheet->setWildcardTemplate('bar', 'BAR');

		$configurator->stylesheet->get();
	}

	/**
	* @testdox get() only tests the wildcard templates' safeness against tags that do not have a default template
	*/
	public function testGetUnsafeWildcardDefaultTemplate()
	{
		$configurator = new Configurator;
		$tag = $configurator->tags->add('foo:X');
		$tag->attributes->add('bar');
		$tag->defaultTemplate = 'FOO';

		$configurator->stylesheet->setWildcardTemplate('foo', '<a href="{@bar}">_</a>');

		$configurator->stylesheet->get();
	}

	/**
	* @testdox get() correctly escapes predicates
	*/
	public function testGetEscapesPredicates()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->templates['.>""'] = 'BAR';

		$this->assertContains(
			'match="X[.&gt;&quot;&quot;]"',
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox get() merges duplicate templates
	*/
	public function testGetMergesDuplicateTemplates()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = 'X';
		$configurator->tags->add('Y')->defaultTemplate = 'X';

		$this->assertContains(
			'<xsl:template match="X|Y">X</xsl:template>',
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox get() represents empty templates with a self-closing element
	*/
	public function testGetEmptyTemplates()
	{
		$configurator = new Configurator;

		$this->assertContains(
			'<xsl:template match="e|i|s"/>',
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox get() generates the namespace declarations necessary for prefixed tags
	*/
	public function testGetDeclaresNamespaces()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X:A')->defaultTemplate = 'X';
		$configurator->tags->add('Y:B')->defaultTemplate = 'Y';

		$this->assertContains(
			'xmlns:X="urn:s9e:TextFormatter:X" xmlns:Y="urn:s9e:TextFormatter:Y"',
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox get() generates an exclude-result-prefixes directive for all the declared prefixes
	*/
	public function testGetExcludesPrefixes()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X:A')->defaultTemplate = 'X';
		$configurator->tags->add('Y:B')->defaultTemplate = 'Y';

		$this->assertContains(
			'exclude-result-prefixes="X Y"',
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox get() includes parameters in the stylesheet
	*/
	public function testGetParameters()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->parameters->add('foo');
		$configurator->stylesheet->parameters->add('bar');

		$this->assertContains(
			'<xsl:param name="foo"',
			$configurator->stylesheet->get()
		);

		$this->assertContains(
			'<xsl:param name="bar"',
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox get() includes a parameter's default value in the stylesheet
	*/
	public function testGetParameterValue()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->parameters->add('foo', 'bar');

		$this->assertContains(
			'<xsl:param name="foo" select="\'bar\'"/>',
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox get() escapes a parameter's default value
	*/
	public function testGetParameterValueEscaped()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->parameters->add('foo', '\'"&<>');

		$this->assertContains(
			"<xsl:param name=\"foo\" select=\"concat(&quot;'&quot;,'&quot;&amp;&lt;&gt;')\"/>",
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox get() calls the plugins' finalize() method before assembling the stylesheet
	*/
	public function testGetFinalize()
	{
		$configurator = new Configurator;
		$configurator->plugins->add('Dummy', __NAMESPACE__ . '\\DummyStylesheetPluginConfigurator');
		$configurator->tags->add('FOO')->defaultTemplate = 'BAR';

		$xsl = $configurator->stylesheet->get();

		$this->assertContains('BAZ', $xsl);
		$this->assertNotContains('BAR', $xsl);
	}

	/**
	* @testdox get() normalizes wildcard templates before assembling the stylesheet
	*/
	public function testGetNormalizeWildcard()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->setWildcardTemplate('xxx', '<FOO/>');

		$xsl = $configurator->stylesheet->get();

		$this->assertContains('<foo/>', $xsl);
		$this->assertNotContains('<FOO/>', $xsl);
	}

	/**
	* @testdox get() normalizes tags' templates before assembling the stylesheet
	*/
	public function testGetNormalizeTags()
	{
		$configurator = new Configurator;
		$configurator->tags->add('FOO')->defaultTemplate = '<FOO/>';

		$xsl = $configurator->stylesheet->get();

		$this->assertContains('<foo/>', $xsl);
		$this->assertNotContains('<FOO/>', $xsl);
	}

	/**
	* @testdox getUsedParameters() returns parameters that were formally defined
	*/
	public function testGetUsedParametersDefined()
	{
		$configurator = new Configurator;
		$configurator->stylesheet->parameters->add('foo', 'Foo');

		$this->assertSame(
			['foo' => "'Foo'"],
			$configurator->stylesheet->getUsedParameters()
		);
	}

	/**
	* @testdox getUsedParameters() returns undefined parameters used in tags' templates
	*/
	public function testGetUsedParametersUndefinedFromTemplates()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = '<xsl:value-of select="$L_FOO"/>';
		$configurator->tags->add('Y')->defaultTemplate = '<xsl:value-of select="$S_OK"/>';

		$this->assertSame(
			['L_FOO' => "''", 'S_OK' => "''"],
			$configurator->stylesheet->getUsedParameters()
		);
	}

	/**
	* @testdox getUsedParameters() returns undefined parameters used in tags' templates' predicates
	*/
	public function testGetUsedParametersUndefinedFromTemplatesPredicates()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->templates['$foo'] = '';
		$configurator->tags->add('Y')->templates['not($bar)'] = '';

		$this->assertEquals(
			['foo' => "''", 'bar' => "''"],
			$configurator->stylesheet->getUsedParameters()
		);
	}

	/**
	* @testdox getUsedParameters() returns undefined parameters used in wildcard templates
	*/
	public function testGetUsedParametersUndefinedFromWildcards()
	{
		$configurator = new Configurator;
		$configurator->tags->add('foo:X');

		$configurator->stylesheet->setWildcardTemplate('foo', '<xsl:value-of select="$L_FOO"/>');

		$this->assertSame(
			['L_FOO' => "''"],
			$configurator->stylesheet->getUsedParameters()
		);
	}

	/**
	* @testdox get() outputs <xsl:param/> elements for undefined parameters used in templates
	*/
	public function testGetOutputsUndefinedParameters()
	{
		$configurator = new Configurator;
		$configurator->tags->add('X')->defaultTemplate = '<xsl:value-of select="$L_FOO"/>';

		$this->assertContains(
			'<xsl:param name="L_FOO"',
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox Creates a default template for <br/>
	*/
	public function testDefaultTemplateBr()
	{
		$configurator = new Configurator;

		$this->assertContains(
			'<xsl:template match="br"><br/></xsl:template>',
			$configurator->stylesheet->get()
		);
	}

	/**
	* @testdox Creates a default template for <p></p>
	*/
	public function testDefaultTemplateP()
	{
		$configurator = new Configurator;

		$this->assertContains(
			'<xsl:template match="p"><p><xsl:apply-templates/></p></xsl:template>',
			$configurator->stylesheet->get()
		);
	}
}

class DummyStylesheetPluginConfigurator extends ConfiguratorBase
{
	public function finalize()
	{
		$this->configurator->tags['FOO']->defaultTemplate = 'BAZ';
	}
}