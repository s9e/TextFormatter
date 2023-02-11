<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUncompilableXSL;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\AbstractXSLSupportCheck
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowUncompilableXSL
*/
class DisallowUncompilableXSLTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox Allowed: <b>...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b>...</b>');
		$check = new DisallowUncompilableXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:variable name="foo"/>
	*/
	public function testUnsupportedElement()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('xsl:variable elements are not supported');

		$node = $this->loadTemplate('<xsl:variable name="foo"/>');

		$check = new DisallowUncompilableXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <xsl:copy-of select="@foo"/>
	* @doesNotPerformAssertions
	*/
	public function testCopyOfAttribute()
	{
		$node = $this->loadTemplate('<xsl:copy-of select="@foo"/>');
		$check = new DisallowUncompilableXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:copy-of select="foo"/>
	*/
	public function testCopyOf()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Unsupported xsl:copy-of expression 'foo'");

		$node = $this->loadTemplate('<xsl:copy-of select="foo"/>');

		$check = new DisallowUncompilableXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <xsl:value-of select="string-length(@foo)"/>
	* @doesNotPerformAssertions
	*/
	public function testSupportedFunction()
	{
		$node = $this->loadTemplate('<xsl:value-of select="string-length(@foo)"/>');
		$check = new DisallowUncompilableXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:value-of select="document()"/>
	*/
	public function testUnsupportedFunction()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('XPath function document() is not supported');

		$node = $this->loadTemplate('<xsl:value-of select="document()"/>');
		$check = new DisallowUncompilableXSL;
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
		$check = new DisallowUncompilableXSL;
		$check->check($node, new Tag);
	}
}