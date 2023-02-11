<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowDynamicAttributeNames;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowDynamicAttributeNames
*/
class DisallowDynamicAttributeNamesTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox Disallowed: <b><xsl:attribute name="{@foo}"/></b>
	*/
	public function testDisallowed()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Dynamic <xsl:attribute/> names are disallowed');

		$node = $this->loadTemplate('<b><xsl:attribute name="{@foo}"/></b>');

		try
		{
			$check = new DisallowDynamicAttributeNames;
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

	/**
	* @testdox Allowed: <b><xsl:attribute name="title"/></b>
	* @doesNotPerformAssertions
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b><xsl:attribute name="title"/></b>');

		$check = new DisallowDynamicAttributeNames;
		$check->check($node, new Tag);
	}
}