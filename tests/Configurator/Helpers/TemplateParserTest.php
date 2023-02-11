<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use DOMDocument;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateParser
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateParser\IRProcessor
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateParser\Normalizer
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateParser\Optimizer
* @covers s9e\TextFormatter\Configurator\Helpers\TemplateParser\Parser
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

	public static function getParseTests()
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
	*/
	public function testPI()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Cannot parse node 'pi'");

		TemplateParser::parse('<?pi ?>', 'xml');
	}

	/**
	* @testdox parse() throws an exception if it encounters an unsupported XSL element
	*/
	public function testUnsupportedXSL()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Element 'xsl:foo' is not supported");

		TemplateParser::parse('<xsl:foo/>', 'xml');
	}

	/**
	* @testdox parse() throws an exception if it encounters an unsupported <xsl:copy/> expression
	*/
	public function testUnsupportedCopy()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Unsupported <xsl:copy-of/> expression 'foo'");

		TemplateParser::parse('<xsl:copy-of select="foo"/>', 'xml');
	}
}