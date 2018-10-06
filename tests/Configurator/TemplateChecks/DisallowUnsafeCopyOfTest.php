<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeCopyOf;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsafeCopyOf
*/
class DisallowUnsafeCopyOfTest extends Test
{
	protected function loadTemplate($template)
	{
		$xml = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $template
		     . '</xsl:template>';

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		return $dom->documentElement;
	}

	/**
	* @testdox Allowed: <b><xsl:copy-of select="@title"/></b>
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@title"/></b>');

		$check = new DisallowUnsafeCopyOf;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b><xsl:copy-of select="@data-title"/></b>
	*/
	public function testAllowedDash()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@data-title"/></b>');

		$check = new DisallowUnsafeCopyOf;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Allowed: <b><xsl:copy-of select="@data-title|@title"/></b>
	*/
	public function testAllowedMultipleAttributes()
	{
		$node = $this->loadTemplate('<b><xsl:copy-of select="@data-title|@title"/></b>');

		$check = new DisallowUnsafeCopyOf;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <b><xsl:copy-of select="FOO"/></b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Cannot assess the safety of 'xsl:copy-of' select expression 'FOO'
	*/
	public function testDisallowed()
	{
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