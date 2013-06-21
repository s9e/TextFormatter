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
	public function testParse($xsl, $expectedFile)
	{
		$ir = TemplateParser::parse($xsl);

		$this->assertInstanceOf('DOMDocument', $ir);
		$this->assertXmlStringEqualsXmlFile($expectedFile, $ir->saveXML());
	}

	public function getParseTests()
	{
		$tests = [];
		foreach (glob(__DIR__ . '/data/TemplateParser/*.xsl') as $filepath)
		{
			$dom = new DOMDocument;
			$dom->preserveWhiteSpace = false;
			$dom->load($filepath);

			$tests[] = [$dom->saveXML(), substr($filepath, 0, -3) . 'xml'];
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
		TemplateParser::parse(
			'<?xml version="1.0" encoding="utf-8"?>
			<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

				<xsl:output method="html" encoding="utf-8" />

				<xsl:template match="FOO"><?pi ?></xsl:template>

			</xsl:stylesheet>'
		);
	}

	/**
	* @testdox parse() throws an exception if it encounters an unsupported XSL element
	* @expectedException RuntimeException
	* @expectedExceptionMessage Element 'xsl:foo' is not supported
	*/
	public function testUnsupportedXSL()
	{
		TemplateParser::parse(
			'<?xml version="1.0" encoding="utf-8"?>
			<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

				<xsl:output method="html" encoding="utf-8" />

				<xsl:template match="FOO"><xsl:foo/></xsl:template>

			</xsl:stylesheet>'
		);
	}

	/**
	* @testdox parse() throws an exception if it encounters an unsupported <xsl:copy/> expression
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unsupported <xsl:copy-of/> expression 'foo'
	*/
	public function testUnsupportedCopy()
	{
		TemplateParser::parse(
			'<?xml version="1.0" encoding="utf-8"?>
			<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

				<xsl:output method="html" encoding="utf-8" />

				<xsl:template match="FOO"><xsl:copy-of select="foo"/></xsl:template>

			</xsl:stylesheet>'
		);
	}

	/**
	* @testdox parse() throws an exception if it encounters a non-XSL namespaced element
	* @expectedException RuntimeException
	* @expectedExceptionMessage Namespaced element 'foo:foo' is not supported
	*/
	public function testUnsupportedNS()
	{
		TemplateParser::parse(
			'<?xml version="1.0" encoding="utf-8"?>
			<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

				<xsl:output method="html" encoding="utf-8" />

				<xsl:template match="FOO"><foo:foo xmlns:foo="urn:foo"/></xsl:template>

			</xsl:stylesheet>'
		);
	}
}