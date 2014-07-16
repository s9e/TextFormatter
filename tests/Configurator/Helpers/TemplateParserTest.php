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
	public function testParse($template, $outputMethod, $expectedFile)
	{
		$ir = TemplateParser::parse($template, $outputMethod);

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

			$parts = explode('.', $filepath);
			$expectedFile = $parts[0] . '.xml';
			$outputMethod = $parts[1];

			$tests[] = [$template, $outputMethod, $expectedFile];
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
	* @testdox parse() throws an exception if it encounters a non-XSL namespaced element
	* @expectedException RuntimeException
	* @expectedExceptionMessage Namespaced element 'foo:foo' is not supported
	*/
	public function testUnsupportedNS()
	{
		TemplateParser::parse('<foo:foo xmlns:foo="urn:foo"/>', 'xml');
	}
}