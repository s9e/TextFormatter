<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use DOMDocument;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateParser
*/
class TemplateParserTest extends Test
{
	/**
	* @testdox parse() tests
	* @dataProvider getParseTests
	*/
	public function testParse($template, $expectedFile)
	{
		$ir = TemplateParser::parse($template);

		$this->assertInstanceOf('DOMDocument', $ir);
		$this->assertXmlStringEqualsXmlFile($expectedFile, $ir->saveXML());
	}

	public function getParseTests()
	{
		$tests = [];
		foreach (glob(__DIR__ . '/data/TemplateParser/*.template') as $filepath)
		{
			$template = file_get_contents($filepath);

			// Remove inter-element whitespace, it's only there for readability
			$template = preg_replace('(>\\n\\s*<)', '><', $template);

			$expectedFile = substr($filepath, 0, -8) . 'xml';

			$tests[] = [$template, $expectedFile];
		}

		return $tests;
	}

	/**
	* @testdox parse() throws an exception if it encounters a processing instruction in the stylesheet
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot parse node 'pi'
	*/
	public function testPI()
	{
		TemplateParser::parse('<?pi ?>', 'xml');
	}

	/**
	* @testdox parse() throws an exception if it encounters an unsupported XSL element
	* @expectedException RuntimeException
	* @expectedExceptionMessage Element 'xsl:foo' is not supported
	*/
	public function testUnsupportedXSL()
	{
		TemplateParser::parse('<xsl:foo/>', 'xml');
	}

	/**
	* @testdox parse() throws an exception if it encounters an unsupported <xsl:copy/> expression
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unsupported <xsl:copy-of/> expression 'foo'
	*/
	public function testUnsupportedCopy()
	{
		TemplateParser::parse('<xsl:copy-of select="foo"/>', 'xml');
	}

	/**
	* @dataProvider getParseEqualityExprTests
	*/
	public function testParseEqualityExpr($expr, $expected)
	{
		$this->assertSame($expected, TemplateParser::parseEqualityExpr($expr));
	}

	public function getParseEqualityExprTests()
	{
		return [
			[
				'@foo != "bar"',
				false
			],
			[
				'@foo = "bar"',
				['@foo' => ['bar']]
			],
			[
				'@foo = "bar" or @foo = "baz"',
				['@foo' => ['bar', 'baz']]
			],
			[
				'"bar" = @foo or \'baz\' = @foo',
				['@foo' => ['bar', 'baz']]
			],
			[
				'$foo = "bar"',
				['$foo' => ['bar']]
			],
			[
				'.="bar"or.="baz"or.="quux"',
				['.' => ['bar', 'baz', 'quux']]
			],
			[
				'$foo = concat("bar", \'baz\')',
				['$foo' => ['barbaz']]
			],
			[
				'$a = "aa" or $b = "bb"',
				['$a' => ['aa'], '$b' => ['bb']]
			],
		];
	}
}