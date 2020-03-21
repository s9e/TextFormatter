<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsupportedXSL;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractXSLSupportCheck
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsupportedXSL
*/
class DisallowUnsupportedXSLTest extends AbstractTemplateCheckTest
{
	/**
	* @testdox Allowed: <b>...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b>...</b>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:message>..</xsl:message>
	*/
	public function testUnsupportedElement()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('xsl:message elements are not supported');

		$node = $this->loadTemplate('<xsl:message>..</xsl:message>');

		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <xsl:apply-templates/>
	* @doesNotPerformAssertions
	*/
	public function testApplyTemplates()
	{
		$node = $this->loadTemplate('<xsl:apply-templates/>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:apply-templates mode="unsupported"/>
	*/
	public function testApplyTemplatesMode()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('xsl:apply-templates elements do not support the mode attribute');

		$node = $this->loadTemplate('<xsl:apply-templates mode="unsupported"/>');

		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <xsl:copy-of select="@foo"/>
	* @doesNotPerformAssertions
	*/
	public function testCopyOfAttribute()
	{
		$node = $this->loadTemplate('<xsl:copy-of select="@foo"/>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:copy-of/>
	*/
	public function testCopyOf()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('xsl:copy-of elements require a select attribute');

		$node = $this->loadTemplate('<xsl:copy-of/>');

		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <xsl:if test="@foo"/>
	* @doesNotPerformAssertions
	*/
	public function testIfAttribute()
	{
		$node = $this->loadTemplate('<xsl:if test="@foo"/>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:if/>
	*/
	public function testIf()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('xsl:if elements require a test attribute');

		$node = $this->loadTemplate('<xsl:if/>');

		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <xsl:value-of select="@foo"/>
	* @doesNotPerformAssertions
	*/
	public function testValueOfAttribute()
	{
		$node = $this->loadTemplate('<xsl:value-of select="@foo"/>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:value-of/>
	*/
	public function testValueOf()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('xsl:value-of elements require a select attribute');

		$node = $this->loadTemplate('<xsl:value-of/>');

		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <xsl:variable name="foo"/>
	* @doesNotPerformAssertions
	*/
	public function testVariableNamed()
	{
		$node = $this->loadTemplate('<xsl:variable name="foo"/>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:variable/>
	*/
	public function testVariable()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('xsl:variable elements require a name attribute');

		$node = $this->loadTemplate('<xsl:variable/>');

		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <xsl:when test="@foo"/>
	* @doesNotPerformAssertions
	*/
	public function testWhenAttribute()
	{
		$node = $this->loadTemplate('<xsl:when test="@foo"/>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:when/>
	*/
	public function testWhen()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('xsl:when elements require a test attribute');

		$node = $this->loadTemplate('<xsl:when/>');

		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <xsl:value-of select="substring-after('foo()', &quot;bar()&quot;)"/>
	* @doesNotPerformAssertions
	*/
	public function testSupportedFunction()
	{
		$node = $this->loadTemplate('<xsl:value-of select="substring-after(\'foo()\', &quot;bar()&quot;)"/>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:value-of select="foo('bar')"/>
	*/
	public function testUnsupportedFunction()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('XPath function foo() is not supported');

		$node = $this->loadTemplate('<xsl:value-of select="foo(\'bar\')"/>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <hr title="{foo()}"/>
	*/
	public function testUnsupportedFunctionAVT()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('XPath function foo() is not supported');

		$node = $this->loadTemplate('<hr title="{foo()}"/>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}
}