<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\Regexp
*/
class RegexpTest extends Test
{
	/**
	* @testdox __construct() throws an InvalidArgumentException if the regexp is not valid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid regular expression
	*/
	public function testInvalidRegexp()
	{
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
	* @testdox toJS() returns a JavaScript Code
	*/
	public function testToJS()
	{
		$regexp = new Regexp('/foo/i');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Code',
			$regexp->toJS()
		);
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
	* @testdox Is an instance of Variant
	*/
	public function testIsVariant()
	{
		$regexp = new Regexp('//');
		$this->assertInstanceOf('s9e\\TextFormatter\\Configurator\\Items\\Variant', $regexp);
	}

	/**
	* @testdox Has a JS variant
	*/
	public function testJSVariant()
	{
		$regexp = new Regexp('//');

		$this->assertTrue($regexp->has('JS'));
	}

	/**
	* @testdox The JS variant is an instance of s9e\TextFormatter\Configurator\JavaScript\Code
	*/
	public function testJSVariantInstance()
	{
		$regexp = new Regexp('//');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Code',
			$regexp->get('JS')
		);
	}

	/**
	* @testdox The JS regexp has a global flag if isGlobal is true
	*/
	public function testJSVariantGlobal()
	{
		$regexp = new Regexp('/x/', true);
		$this->assertEquals('/x/g', $regexp->get('JS'));
	}

	/**
	* @testdox getNamedCaptures() returns an array where keys are the name of the named captures and values are regexps that exactly match them
	*/
	public function testGetNamedCaptures()
	{
		$ap = new Regexp('#(?<year>\\d{4}) (?<name>[a-z]+)#');

		$this->assertSame(
			[
				'year' => '#^(?:\\d{4})$#D',
				'name' => '#^(?:[a-z]+)$#D'
			],
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
				'year' => '#^(?:\\d{4})$#Disu',
				'name' => '#^(?:[a-z]+)$#Disu'
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
}