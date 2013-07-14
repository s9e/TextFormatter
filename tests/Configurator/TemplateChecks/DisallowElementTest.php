<?php

namespace s9e\TextFormatter\Tests\Configurator\TemplateChecks;

use DOMDocument;
use DOMNode;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\TemplateChecks\DisallowElement;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\TemplateChecks\DisallowElement
*/
class DisallowElementTest extends Test
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
	* @testdox DisallowElement('script') disallows <b><script/></b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Element 'script' is disallowed
	*/
	public function testDisallowed()
	{
		$node = $this->loadTemplate('<b><script/></b>');

		try
		{
			$check = new DisallowElement('script');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox DisallowElement('svg') disallows <svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Element 'svg' is disallowed
	*/
	public function testDisallowedNS()
	{
		$node = $this->loadTemplate('<svg:svg xmlns:svg="http://www.w3.org/2000/svg"/>');

		try
		{
			$check = new DisallowElement('svg');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox DisallowElement('script') allows <b><span/></b>
	*/
	public function testAllowed()
	{
		$node = $this->loadTemplate('<b><span/></b>');

		$check = new DisallowElement('script');
		$check->check($node, new Tag);
	}

	/**
	* @testdox DisallowElement('script') disallows <b><SCRIPT/></b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Element 'script' is disallowed
	*/
	public function testDisallowedUppercase()
	{
		$node = $this->loadTemplate('<b><SCRIPT/></b>');

		try
		{
			$check = new DisallowElement('script');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}

	/**
	* @testdox DisallowElement('script') disallows <b><xsl:element name="script"/></b>
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	* @expectedExceptionMessage Element 'script' is disallowed
	*/
	public function testDisallowedDynamic()
	{
		$node = $this->loadTemplate('<b><xsl:element name="script"/></b>');

		try
		{
			$check = new DisallowElement('script');
			$check->check($node, new Tag);
		}
		catch (UnsafeTemplateException $e)
		{
			$this->assertSame($e->getNode(), $node->firstChild->firstChild);

			throw $e;
		}
	}
}