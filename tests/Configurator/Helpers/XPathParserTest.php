<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use DOMDocument;
use s9e\TextFormatter\Configurator\Helpers\XPathParser;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\XPathParser
*/
class XPathParserTest extends Test
{
	public function setUp()
	{
		if (!empty($_SERVER['travis']) && PCRE_VERSION < 8.11)
		{
			$this->markTestSkipped('PCRE < 8.11 seems to segfault on some tests');
		}
	}

	/**
	* @testdox parse() tests
	* @dataProvider getParseTests
	*/
	public function testParse($tokenName, $xsl, $expectedFile)
	{
		$ir = XPathParser::parse($xsl, $tokenName);

		$this->assertInstanceOf('DOMDocument', $ir);
		$this->assertXmlStringEqualsXmlFile($expectedFile, $ir->saveXML());
	}

	public function getParseTests()
	{
		$tests = [];
		foreach (glob(__DIR__ . '/data/XPathParser/*.txt') as $filepath)
		{
			$xmlFilepath = substr($filepath, 0, -3) . 'xml';
			preg_match('/<([A-Z]\\w+)>/', file_get_contents($xmlFilepath), $m);

			$tests[] = [$m[1], file_get_contents($filepath), $xmlFilepath];
		}

		return $tests;
	}

	/**
	* @testdox parse() throws an exception if the expression cannot be parsed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot parse 'x x x' as Expr
	*/
	public function testInvalid()
	{
		XPathParser::parse('x x x');
	}
}