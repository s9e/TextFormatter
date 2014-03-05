<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMElement;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowElementNS;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowElementNS
*/
class DisallowElementNSTest extends Test
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
	* @testdox DisallowElementNS('http://www.w3.org/2000/svg', 'svg') disallows <svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Element 'svg:svg' is disallowed
	*/
	public function testDisallowed()
	{
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
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Element 'svg' is disallowed
	*/
	public function testDisallowedDefaultNS()
	{
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
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b><script/></b>');

		$check = new DisallowElementNS('urn:foo', 'script');
		$check->check($node, new Tag);
	}
}