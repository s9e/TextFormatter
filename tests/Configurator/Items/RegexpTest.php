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
	* @testdox toJS() returns a JavaScript RegExp
	*/
	public function testToJS()
	{
		$regexp = new Regexp('/foo/i');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\RegExp',
			$regexp->toJS()
		);
	}

	/**
	* @testdox asConfig() returns an instance of Variant
	*/
	public function testAsConfigInstance()
	{
		$regexp = new Regexp('//');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$regexp->asConfig()
		);
	}

	/**
	* @testdox asConfig() returns a JS variant
	*/
	public function testAsConfigJSVariant()
	{
		$regexp = new Regexp('//');

		$this->assertTrue($regexp->asConfig()->has('JS'));
	}

	/**
	* @testdox asConfig() returns a JS variant that is an instance of s9e\TextFormatter\Configurator\JavaScript\RegExp
	*/
	public function testAsConfigJSVariantInstance()
	{
		$regexp = new Regexp('//');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\RegExp',
			$regexp->asConfig()->get('JS')
		);
	}

	/**
	* @testdox asConfig() adds a global flag to the JavaScript RegExp if isGlobal is true
	*/
	public function testAsConfigGlobal()
	{
		$regexp = new Regexp('//', true);

		$this->assertSame(
			'g',
			$regexp->asConfig()->get('JS')->flags
		);
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
}