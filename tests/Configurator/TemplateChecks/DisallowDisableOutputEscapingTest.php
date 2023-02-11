<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowDisableOutputEscaping;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowDisableOutputEscaping
*/
class DisallowDisableOutputEscapingTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox Disallowed: <b disable-output-escaping="1"/>
	*/
	public function test()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("The template contains a 'disable-output-escaping' attribute");

		$node = $this->loadTemplate('<b disable-output-escaping="1"/>');

		try
		{
			$check = new DisallowDisableOutputEscaping;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('disable-output-escaping')
				)
			);

			throw $e;
		}
	}

	/**
	* @testdox Allowed: <b>...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b>...</b>');
		$check = new DisallowDisableOutputEscaping;
		$check->check($node, new Tag);
	}
}