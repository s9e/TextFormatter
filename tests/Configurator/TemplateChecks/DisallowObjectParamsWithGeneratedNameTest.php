<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowObjectParamsWithGeneratedName;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowObjectParamsWithGeneratedName
*/
class DisallowObjectParamsWithGeneratedNameTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox Allowed: <object><param name="foo"/></object>
	* @doesNotPerformAssertions
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<object><param name="foo"/></object>');

		$check = new DisallowObjectParamsWithGeneratedName;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <object><param name="{@foo"/></object>
	*/
	public function testDisallowedInline()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("A 'param' element with a suspect name has been found");

		$node = $this->loadTemplate('<object><param name="{@foo}"/></object>');

		try
		{
			$check = new DisallowObjectParamsWithGeneratedName;
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
	* @testdox Disallowed: <object><param name="{@foo"/></object>
	*/
	public function testDisallowedGenerated()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("A 'param' element with a suspect name has been found");

		$node = $this->loadTemplate('<object><param><xsl:if test="@foo"><xsl:attribute name="name"><xsl:value-of select="@bar"/></xsl:attribute></xsl:if></param></object>');

		try
		{
			$check = new DisallowObjectParamsWithGeneratedName;
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