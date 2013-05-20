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
	* @testdox Constructor accepts a string
	*/
	public function testAcceptsString()
	{
		new Template('');
	}

	/**
	* @testdox Constructor accepts a valid callback
	*/
	public function testAcceptsCallback()
	{
		new Template(function(){});
	}

	/**
	* @testdox Constructor throws an exception on anything else
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be a string or a valid callback
	*/
	public function testRejectsEverythingElse()
	{
		new Template(false);
	}

	/**
	* @testdox Constructor normalizes string templates
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\InvalidXslException
	* @expectedExceptionMessage Premature end of data
	*/
	public function testNormalizeTemplate()
	{
		new Template('<xsl:value-of select="@foo">');
	}

	/**
	* @testdox When cast as string, returns the string passed to constructor if applicable
	*/
	public function testToStringString()
	{
		$template = new Template('foo');

		$this->assertSame('foo', (string) $template);
	}

	/**
	* @testdox When cast as string, executes the callback passed to constructor and returns its result if applicable
	*/
	public function testToStringCallback()
	{
		$template = new Template(
			function ()
			{
				return 'foo';
			}
		);

		$this->assertSame('foo', (string) $template);
	}

	/**
	* @testdox When cast as string, executes the callback passed and normalizes the returned template
	*/
	public function testToStringCallbackNormalize()
	{
		$template = new Template(
			function ()
			{
				return '<xsl:text>foo</xsl:text><xsl:text>bar</xsl:text>';
			}
		);

		$this->assertSame('foobar', (string) $template);
	}

	/**
	* @testdox Constructor interprets a string as a literal, even if it would be a valid callback
	*/
	public function testConstructorStringLiteral()
	{
		$template = new Template('uniqid');

		$this->assertSame('uniqid', (string) $template);
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
}