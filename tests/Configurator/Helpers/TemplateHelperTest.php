<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use Exception;
use RuntimeException;
use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateHelper
*/
class TemplateHelperTest extends Test
{
	/**
	* @testdox loadTemplate() can load 'foo'
	*/
	public function testLoadText()
	{
		$text = 'foo';

		$dom = TemplateHelper::loadTemplate($text);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertContains($text, $dom->saveXML());
	}

	/**
	* @testdox saveTemplate() correctly handles 'foo'
	*/
	public function testSaveText()
	{
		$text = 'foo';

		$this->assertSame($text, TemplateHelper::saveTemplate(TemplateHelper::loadTemplate($text)));
	}

	/**
	* @testdox loadTemplate() can load '<xsl:value-of select="@foo"/>'
	*/
	public function testLoadXSL()
	{
		$xsl = '<xsl:value-of select="@foo"/>';

		$dom = TemplateHelper::loadTemplate($xsl);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertContains($xsl, $dom->saveXML());
	}

	/**
	* @testdox saveTemplate() correctly handles '<xsl:value-of select="@foo"/>'
	*/
	public function testSaveXSL()
	{
		$xsl = '<xsl:value-of select="@foo"/>';

		$this->assertSame($xsl, TemplateHelper::saveTemplate(TemplateHelper::loadTemplate($xsl)));
	}

	/**
	* @testdox saveTemplate() correctly handles an empty string
	*/
	public function testSaveXSLEmpty()
	{
		$xsl = '';

		$this->assertSame($xsl, TemplateHelper::saveTemplate(TemplateHelper::loadTemplate($xsl)));
	}

	/**
	* @testdox loadTemplate() can load '<ul><li>one<li>two</ul>'
	*/
	public function testLoadHTML()
	{
		$html = '<ul><li>one<li>two</ul>';
		$xml  = '<ul><li>one</li><li>two</li></ul>';

		$dom = TemplateHelper::loadTemplate($html);
		$this->assertInstanceOf('DOMDocument', $dom);

		$this->assertContains($xml, $dom->saveXML());
	}

	/**
	* @testdox loadTemplate() can load '<ul><li>one<li>two</ul>'
	* @depends testLoadHTML
	*/
	public function testLoadHTMLInNamespace()
	{
		$html = '<ul><li>one<li>two</ul>';

		$this->assertSame(
			'http://www.w3.org/1999/XSL/Transform',
			TemplateHelper::loadTemplate($html)->lookupNamespaceURI('xsl')
		);
	}

	/**
	* @testdox saveTemplate() correctly handles '<ul><li>one<li>two</ul>'
	*/
	public function testSaveHTML()
	{
		$html = '<ul><li>one<li>two</ul>';
		$xml  = '<ul><li>one</li><li>two</li></ul>';

		$this->assertSame($xml, TemplateHelper::saveTemplate(TemplateHelper::loadTemplate($html)));
	}

	/**
	* @testdox loadTemplate() throws an exception on malformed XSL
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\InvalidXslException
	* @expectedExceptionMessage Premature end of data
	*/
	public function testLoadInvalidXSL()
	{
		$xsl = '<xsl:value-of select="@foo">';
		TemplateHelper::loadTemplate($xsl);
	}

	/**
	* @testdox normalize() normalizes '<br>' to '<br/>'
	*/
	public function testNormalize()
	{
		$this->assertSame(
			'<br/>',
			TemplateHelper::normalize('<br>')
		);
	}

	/**
	* @testdox normalize() throws an exception on malformed XSL
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\InvalidXslException
	* @expectedExceptionMessage Premature end of data
	*/
	public function testNormalizeInvalid()
	{
		TemplateHelper::normalize('<xsl:value-of select="@foo">');
	}

	/**
	* @testdox parseAttributeValueTemplate() tests
	* @dataProvider getAVT
	*/
	public function testParseAttributeValueTemplate($attrValue, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$this->assertSame(
			$expected,
			TemplateHelper::parseAttributeValueTemplate($attrValue)
		);
	}

	public function getAVT()
	{
		return [
			[
				'',
				[]
			],
			[
				'foo',
				[
					['literal', 'foo']
				]
			],
			[
				'foo {@bar} baz',
				[
					['literal',    'foo '],
					['expression', '@bar'],
					['literal',    ' baz']
				]
			],
			[
				'foo {{@bar}} baz',
				[
					['literal', 'foo '],
					['literal', '{'],
					['literal', '@bar} baz']
				]
			],
			[
				'foo {@bar}{baz} quux',
				[
					['literal',    'foo '],
					['expression', '@bar'],
					['expression', 'baz'],
					['literal',    ' quux']
				]
			],
			[
				'foo {"bar"} baz',
				[
					['literal',    'foo '],
					['expression', '"bar"'],
					['literal',    ' baz']
				]
			],
			[
				"foo {'bar'} baz",
				[
					['literal',    'foo '],
					['expression', "'bar'"],
					['literal',    ' baz']
				]
			],
			[
				'foo {"\'bar\'"} baz',
				[
					['literal',    'foo '],
					['expression', '"\'bar\'"'],
					['literal',    ' baz']
				]
			],
			[
				'foo {"{bar}"} baz',
				[
					['literal',    'foo '],
					['expression', '"{bar}"'],
					['literal',    ' baz']
				]
			],
			[
				'foo {"bar} baz',
				new RuntimeException('Unterminated XPath expression')
			],
			[
				'foo {bar',
				new RuntimeException('Unterminated XPath expression')
			],
			[
				'<foo> {"<bar>"} &amp;',
				[
					['literal',    '<foo> '],
					['expression', '"<bar>"'],
					['literal',    ' &amp;']
				]
			],
		];
	}
}