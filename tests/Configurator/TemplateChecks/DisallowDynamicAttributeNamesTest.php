<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowDynamicAttributeNames;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowDynamicAttributeNames
*/
class DisallowDynamicAttributeNamesTest extends Test
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
	* @testdox Disallowed: <b><xsl:attribute name="{@foo}"/></b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Dynamic <xsl:attribute/> names are disallowed
	*/
	public function testDisallowed()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="{@foo}"/></b>');

		try
		{
			$check = new DisallowDynamicAttributeNames;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox Allowed: <b><xsl:attribute name="title"/></b>
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="title"/></b>');

		$check = new DisallowDynamicAttributeNames;
		$check->check($node, new Tag);
	}
}