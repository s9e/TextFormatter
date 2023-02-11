<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeCopyOf;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeCopyOf
*/
class DisallowUnsafeCopyOfTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox Allowed: <b><xsl:copy-of select="@title"/></b>
	* @doesNotPerformAssertions
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@title"/></b>');

		$check = new DisallowUnsafeCopyOf;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b><xsl:copy-of select="@data-title"/></b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedDash()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@data-title"/></b>');

		$check = new DisallowUnsafeCopyOf;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b><xsl:copy-of select="@data-title|@title"/></b>
	* @doesNotPerformAssertions
	*/
	public function testAllowedMultipleAttributes()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@data-title|@title"/></b>');

		$check = new DisallowUnsafeCopyOf;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <b><xsl:copy-of select="FOO"/></b>
	*/
	public function testDisallowed()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of 'xsl:copy-of' select expression 'FOO'");

		$node = $this->loadTemplate('<b><xsl:copy-of select="FOO"/></b>');

		try
		{
			$check = new DisallowUnsafeCopyOf;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->firstChild
				)
			);

			throw $e;
		}
	}
}