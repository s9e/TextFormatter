<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\DynamicStylesheetParameter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\StylesheetParameter
* @covers s9e\TextFormatter\Configurator\Items\DynamicStylesheetParameter
*/
class DynamicStylesheetParameterTest extends Test
{
	/**
	* @testdox __construct() does not require an argument
	*/
	public function testConstructor()
	{
		$parameter = new DynamicStylesheetParameter;
	}

	/**
	* @testdox __construct() can take the parameter's default value as argument
	*/
	public function testConstructorArgument()
	{
		$parameter = new DynamicStylesheetParameter('foo');
		$this->assertAttributeContains('foo', 'value', $parameter);
	}

	/**
	* @testdox Returns its default value as an XPath expression when cast as a string
	*/
	public function testToString()
	{
		$parameter = new DynamicStylesheetParameter("'foo'");
		$this->assertSame("'foo'", (string) $parameter);
	}

	/**
	* @testdox Minifies the XPath expression passed to the constructor
	*/
	public function testConstructorMinify()
	{
		$parameter = new DynamicStylesheetParameter(' concat( $foo, "bar" ) ');
		$this->assertSame('concat($foo,"bar")', (string) $parameter);
	}

	/**
	* @testdox isSafeAsURL() returns false by default
	*/
	public function testIsSafeAsURLFalse()
	{
		$parameter = new DynamicStylesheetParameter;
		$this->assertFalse($parameter->isSafeAsURL());
	}

	/**
	* @testdox isSafeAsURL() returns true if markAsSafeAsURL() was called
	*/
	public function testIsSafeAsURLTrue()
	{
		$parameter = new DynamicStylesheetParameter;
		$parameter->markAsSafeAsURL();
		$this->assertTrue($parameter->isSafeAsURL());
	}

	/**
	* @testdox isSafeInCSS() returns false by default
	*/
	public function testIsSafeInCSSFalse()
	{
		$parameter = new DynamicStylesheetParameter;
		$this->assertFalse($parameter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns true if markAsSafeInCSS() was called
	*/
	public function testIsSafeInCSSTrue()
	{
		$parameter = new DynamicStylesheetParameter;
		$parameter->markAsSafeInCSS();
		$this->assertTrue($parameter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInJS() returns false by default
	*/
	public function testIsSafeInJSFalse()
	{
		$parameter = new DynamicStylesheetParameter;
		$this->assertFalse($parameter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns true if markAsSafeInJS() was called
	*/
	public function testIsSafeInJSTrue()
	{
		$parameter = new DynamicStylesheetParameter;
		$parameter->markAsSafeInJS();
		$this->assertTrue($parameter->isSafeInJS());
	}
}