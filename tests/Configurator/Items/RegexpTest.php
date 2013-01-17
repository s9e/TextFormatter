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
}