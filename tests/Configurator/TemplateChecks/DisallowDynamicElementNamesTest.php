<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowDynamicElementNames;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowDynamicElementNames
*/
class DisallowDynamicElementNamesTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox Disallowed: <xsl:element name="{s}"/>
	*/
	public function testDisallowed()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Dynamic <xsl:element/> names are disallowed');

		$node = $this->loadTemplate('<xsl:element name="{s}"/>');

		try
		{
			$check = new DisallowDynamicElementNames;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed: <xsl:element name="b"/>
	* @doesNotPerformAssertions
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<xsl:element name="b"/>');

		$check = new DisallowDynamicElementNames;
		$check->check($node, new Tag);
	}
}