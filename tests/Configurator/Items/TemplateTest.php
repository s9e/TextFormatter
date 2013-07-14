<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\Template
*/
class TemplateTest extends Test
{
	/**
	* @testdox When cast as string, returns the template's content
	*/
	public function testToStringString()
	{
		$template = new Template('foo');

		$this->assertSame('foo', (string) $template);
	}

	/**
	* @testdox getParameters() returns the list of parameters used in this template
	*/
	public function testGetParameters()
	{
		$template = new Template('<div><xsl:value-of select="$L_FOO"/></div>');

		$this->assertSame(
			['L_FOO'],
			$template->getParameters()
		);
	}

	/**
	* @testdox asDOM() returns the template as a DOMDocument
	*/
	public function testAsDOM()
	{
		$xml      = '<div>foo</div>';
		$template = new Template($xml);

		$this->assertInstanceOf('DOMDocument', $template->asDOM());
		$this->assertContains($xml, $template->asDOM()->saveXML());
	}

	/**
	* @testdox getCSSNodes() returns all nodes that normally contain CSS
	*/
	public function testGetCSSNodes()
	{
		$template = new Template('<div style="color:red" onclick="alert(1)">foo</div>');
		$nodes    = $template->getCSSNodes();

		$this->assertSame(1, count($nodes));
		$this->assertSame('color:red', $nodes[0]->value);
	}

	/**
	* @testdox getJSNodes() returns all nodes that normally contain JS
	*/
	public function testGetJSNodes()
	{
		$template = new Template('<div style="color:red" onclick="alert(1)">foo</div>');
		$nodes    = $template->getJSNodes();

		$this->assertSame(1, count($nodes));
		$this->assertSame('alert(1)', $nodes[0]->value);
	}

	/**
	* @testdox getURLNodes() returns all nodes that normally contain a URL
	*/
	public function testGetURLNodes()
	{
		$template = new Template('<a href="{@foo}">...</a>');
		$nodes    = $template->getURLNodes();

		$this->assertSame(1, count($nodes));
		$this->assertSame('{@foo}', $nodes[0]->value);
	}

	/**
	* @testdox isNormalized() returns false by default
	*/
	public function testIsNormalizedDefault()
	{
		$template = new Template('<br/>');

		$this->assertFalse($template->isNormalized());
	}

	/**
	* @testdox isNormalized() returns true if normalize() was called
	*/
	public function testIsNormalizedCalled()
	{
		$mock = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\TemplateNormalizer')
		             ->disableOriginalConstructor()
		             ->getMock();
		
		$template = new Template('<br/>');
		$template->normalize($mock);

		$this->assertTrue($template->isNormalized());
	}

	/**
	* @testdox isNormalized(true) sets it to true
	*/
	public function testIsNormalizedTrue()
	{
		$template = new Template('<br/>');

		$template->isNormalized(true);
		$this->assertTrue($template->isNormalized());
	}

	/**
	* @testdox isNormalized(false) sets it to false
	*/
	public function testIsNormalizedFalse()
	{
		$template = new Template('<br/>');

		$template->isNormalized(true);
		$template->isNormalized(false);
		$this->assertFalse($template->isNormalized());
	}
}