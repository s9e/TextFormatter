<?php

namespace s9e\TextFormatter\Utils;

use Exception;
use RuntimeException;
use s9e\TextFormatter\Utils\XPath;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Utils\XPath
*/
class XPathTest extends Test
{
	/**
	* @testdox export('foo') returns 'foo'
	*/
	public function testExportSingleQuotes()
	{
		$this->assertSame("'foo'", XPath::export('foo'));
	}

	/**
	* @testdox export("d'oh") returns "d'oh"
	*/
	public function testExportDoubleQuotes()
	{
		$this->assertSame('"d\'oh"', XPath::export("d'oh"));
	}

	/**
	* @testdox export("'\"") returns concat("'",'"')
	*/
	public function testExportBothQuotes1()
	{
		$this->assertSame("concat(\"'\",'\"')", XPath::export("'\""));
	}

	/**
	* @testdox export('"\'') returns concat('"',"'")
	*/
	public function testExportBothQuotes2()
	{
		$this->assertSame("concat('\"',\"'\")", XPath::export('"\''));
	}

	/**
	* @testdox export(123) returns 123
	*/
	public function testExportInteger()
	{
		$this->assertSame('123', XPath::export(123));
	}

	/**
	* @testdox export(123.45) returns 123.45
	*/
	public function testExportFloat()
	{
		$this->assertSame('123.45', XPath::export(123.45));
	}

	/**
	* @testdox export(123.45) returns 123.45 regardless of locale
	* @runInSeparateProcess
	*/
	public function testExportFloatLocale()
	{
		if (!setlocale(LC_NUMERIC, 'en_DK.utf8', 'fr_FR'))
		{
			$this->markTestSkipped('Cannot set locale');
		}
		$this->assertSame('123.45', XPath::export(123.45));
	}

	/**
	* @testdox export(new stdClass) throws an exception
	*/
	public function testExportObject()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('non-scalar');

		XPath::export(new \stdClass);
	}

	/**
	* @testdox export(false) returns 'false()'
	*/
	public function testExportFalse()
	{
		$this->assertSame('false()', XPath::export(false));
	}

	/**
	* @testdox export(true) returns 'true()'
	*/
	public function testExportTrue()
	{
		$this->assertSame('true()', XPath::export(true));
	}

	/**
	* @testdox export(INF) throws an exception
	*/
	public function testExportInfinite()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('irrational');

		XPath::export(INF);
	}
}