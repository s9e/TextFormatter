<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowElementNS;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowElementNS
*/
class DisallowElementNSTest extends AbstractTemplateCheckTest
{
	/**
	* @testdox DisallowElementNS('http://www.w3.org/2000/svg', 'svg') disallows <svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>
	*/
	public function testDisallowed()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Element 'svg:svg' is disallowed");

		$node = $this->loadTemplate('<svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>');

		try
		{
			$check = new DisallowElementNS('http://www.w3.org/2000/svg', 'svg');
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
	* @testdox DisallowElementNS('http://www.w3.org/2000/svg', 'svg') disallows <svg xmlns="http://www.w3.org/2000/svg"/>
	*/
	public function testDisallowedDefaultNS()
	{
		$this->expectException('s9e\\TextFormatter\\Configurator\\Exceptions\\UnsafeTemplateException');
		$this->expectExceptionMessage("Element 'svg' is disallowed");

		$node = $this->loadTemplate('<svg xmlns="http://www.w3.org/2000/svg"/>');

		try
		{
			$check = new DisallowElementNS('http://www.w3.org/2000/svg', 'svg');
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
	* @testdox DisallowElementNS('urn:foo', 'script') allows <b><script/></b>
	* @doesNotPerformAssertions
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b><script/></b>');

		$check = new DisallowElementNS('urn:foo', 'script');
		$check->check($node, new Tag);
	}
}