<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowCopy;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowCopy
*/
class DisallowCopyTest extends AbstractTemplateCheckTestClass
{
	/**
	* @testdox Disallowed: <b><xsl:copy/></b>
	*/
	public function test()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Cannot assess the safety of an 'xsl:copy' element");

		$node = $this->loadTemplate('<b><xsl:copy/></b>');

		try
		{
			$check = new DisallowCopy;
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
	* @testdox Allowed: <b>...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b>...</b>');
		$check = new DisallowCopy;
		$check->check($node, new Tag);
	}
}