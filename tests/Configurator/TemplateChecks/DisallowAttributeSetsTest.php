<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowAttributeSets;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowAttributeSets
*/
class DisallowAttributeSetsTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox Disallowed: <b use-attribute-sets="foo"/>
	*/
	public function test()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage('Cannot assess the safety of attribute sets');

		$node = $this->loadTemplate('<b use-attribute-sets="foo"/>');

		try
		{
			$check = new DisallowAttributeSets;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue(
				$e->getNode()->isSameNode(
					$node->firstChild->getAttributeNode('use-attribute-sets')
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
		$check = new DisallowAttributeSets;
		$check->check($node, new Tag);
	}
}