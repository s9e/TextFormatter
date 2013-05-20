<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMNode;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowObjectParamsWithGeneratedName;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowObjectParamsWithGeneratedName
*/
class DisallowObjectParamsWithGeneratedNameTest extends Test
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
	* @testdox Allowed: <object><param name="foo"/></object>
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<object><param name="foo"/></object>');

		$check = new DisallowObjectParamsWithGeneratedName;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <object><param name="{@foo"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage A 'param' element with a suspect name has been found
	*/
	public function testDisallowedInline()
	{
		$node = $this->loadTemplate('<object><param name="{@foo}"/></object>');

		try
		{
			$check = new DisallowObjectParamsWithGeneratedName;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Disallowed: <object><param name="{@foo"/></object>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage A 'param' element with a suspect name has been found
	*/
	public function testDisallowedGenerated()
	{
		$node = $this->loadTemplate('<object><param><xsl:if test="@foo"><xsl:attribute name="name"><xsl:value-of select="@bar"/></xsl:attribute></xsl:if></param></object>');

		try
		{
			$check = new DisallowObjectParamsWithGeneratedName;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}
}