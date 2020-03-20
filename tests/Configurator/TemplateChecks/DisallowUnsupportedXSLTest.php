<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsupportedXSL;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowUnsupportedXSL
*/
class DisallowUnsupportedXSLTest extends AbstractTemplateCheckTest
{
	/**
	* @testdox Allowed: <b>...</b>
	* @doesNotPerformAssertions
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b>...</b>');
		$check = new DisallowUnsupportedXSL;
		$check->check($node, new Tag);
	}

	/**
	* @testdox Disallowed: <xsl:message>..</xsl:message>
	*/
	public function test()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('xsl:message elements are not supported');

		$node = $this->loadTemplate('<xsl:message>..</xsl:message>');

		try
		{
			$check = new DisallowUnsupportedXSL;
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertTrue($e->getNode()->isSameNode($node->firstChild));

			throw $e;
		}
	}
}