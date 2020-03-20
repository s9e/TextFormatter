<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsupportedXSL;

/**
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
}