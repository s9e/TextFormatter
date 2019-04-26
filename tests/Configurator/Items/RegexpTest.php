<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\Regexp
*/
class RegexpTest extends Test
{
	/**
	* @testdox __construct() throws an InvalidArgumentException if the regexp is not valid
	*/
	public function testInvalidRegexp()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('Invalid regular expression');

		new Regexp('(?)');
	}

	/**
	* @testdox Can be cast as a string
	*/
	public function testToString()
	{
		$this->assertSame(
			'/foo/i',
			(string) new Regexp('/foo/i')
		);
	}

	/**
	* @testdox getJS() returns the regexp as a string
	*/
	public function testGetJS()
	{
		$regexp = new Regexp('/foo/i');

		$this->assertSame('/foo/i', $regexp->getJS());
	}

	/**
	* @testdox Is a config provider
	*/
	public function testIsConfigProvider()
	{
		$regexp = new Regexp('//');
		$this->assertInstanceOf('s9e\\TextFormatter\\Configurator\\ConfigProvider', $regexp);
	}

	/**
	* @testdox Returns itself as config
	*/
	public function testAsConfigReturnsItself()
	{
		$regexp = new Regexp('//');
		$this->assertSame($regexp, $regexp->asConfig());
	}

	/**
	* @testdox The JS regexp has a global flag if isGlobal is true
	*/
	public function testJSGlobal()
	{
		$regexp = new Regexp('/x/', true);
		$this->assertEquals('/x/g', $regexp->getJS());
	}

	/**
	* @testdox getNamedCaptures() returns an array where keys are the name of the named captures and values are regexps that exactly match them
	*/
	public function testGetNamedCaptures()
	{
		$ap = new Regexp('#(?<year>\\d{4}) (?<name>[a-z]+)#');

		$this->assertSame(
			[
				'year' => '#^\\d{4}$#D',
				'name' => '#^[a-z]+$#D'
			],
			$ap->getNamedCaptures()
		);
	}

	/**
	* @testdox getNamedCaptures() puts the expression in a non-capturing subpattern if it contains any alternations
	*/
	public function testGetNamedCapturesAltern()
	{
		$ap = new Regexp('#(?<foo>foo|bar)#');

		$this->assertSame(
			['foo' => '#^(?:foo|bar)$#D'],
			$ap->getNamedCaptures()
		);
	}

	/**
	* @testdox getNamedCaptures() preserves the original's regexp "i", "s" and "u" flags
	*/
	public function testGetNamedCapturesFlags()
	{
		$ap = new Regexp('#(?<year>\\d{4}) (?<name>[a-z]+)#Disu');

		$this->assertSame(
			[
				'year' => '#^\\d{4}$#Disu',
				'name' => '#^[a-z]+$#Disu'
			],
			$ap->getNamedCaptures()
		);
	}

	/**
	* @testdox getCaptureNames() returns the names of all captures
	*/
	public function testGetCaptureNames()
	{
		$ap = new Regexp('#(?<year>\\d{4}) (?<name>[a-z]+)#');

		$this->assertSame(
			['', 'year', 'name'],
			$ap->getCaptureNames()
		);
	}

	/**
	* @testdox filterConfig('PHP') returns the regexp as a string
	*/
	public function testFilterConfigPHP()
	{
		$regexp = new Regexp('/foo/');

		$this->assertSame(
			'/foo/',
			$regexp->filterConfig('PHP')
		);
	}

	/**
	* @testdox filterConfig('JS') returns a Code object
	*/
	public function testFilterConfigJS()
	{
		$regexp = new Regexp('/foo/', true);

		$this->assertEquals(
			new Code('/foo/g'),
			$regexp->filterConfig('JS')
		);
	}
}