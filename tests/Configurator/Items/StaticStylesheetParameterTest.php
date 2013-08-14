<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\StaticStylesheetParameter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\StylesheetParameter
* @covers s9e\TextFormatter\Configurator\Items\StaticStylesheetParameter
*/
class StaticStylesheetParameterTest extends Test
{
	/**
	* @testdox __construct() does not require an argument
	*/
	public function testConstructor()
	{
		$parameter = new StaticStylesheetParameter;
	}

	/**
	* @testdox __construct() can take the parameter's default value as argument
	*/
	public function testConstructorArgument()
	{
		$parameter = new StaticStylesheetParameter('foo');
		$this->assertAttributeContains('foo', 'value', $parameter);
	}

	/**
	* @testdox Returns its default value as an XPath expression when cast as a string
	*/
	public function testToString()
	{
		$parameter = new StaticStylesheetParameter;
		$this->assertSame("''", (string) $parameter);
	}

	/**
	* @testdox isSafeAsURL() returns false by default
	*/
	public function testIsSafeAsURLFalse()
	{
		$parameter = new StaticStylesheetParameter;
		$this->assertFalse($parameter->isSafeAsURL());
	}

	/**
	* @testdox isSafeAsURL() returns true if markAsSafeAsURL() was called
	*/
	public function testIsSafeAsURLTrue()
	{
		$parameter = new StaticStylesheetParameter;
		$parameter->markAsSafeAsURL();
		$this->assertTrue($parameter->isSafeAsURL());
	}

	/**
	* @testdox isSafeInCSS() returns false by default
	*/
	public function testIsSafeInCSSFalse()
	{
		$parameter = new StaticStylesheetParameter;
		$this->assertFalse($parameter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns true if markAsSafeInCSS() was called
	*/
	public function testIsSafeInCSSTrue()
	{
		$parameter = new StaticStylesheetParameter;
		$parameter->markAsSafeInCSS();
		$this->assertTrue($parameter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInJS() returns false by default
	*/
	public function testIsSafeInJSFalse()
	{
		$parameter = new StaticStylesheetParameter;
		$this->assertFalse($parameter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns true if markAsSafeInJS() was called
	*/
	public function testIsSafeInJSTrue()
	{
		$parameter = new StaticStylesheetParameter;
		$parameter->markAsSafeInJS();
		$this->assertTrue($parameter->isSafeInJS());
	}

	/**
	* @testdox getValue() returns the literal value of the parameter
	*/
	public function testGetValue()
	{
		$parameter = new StaticStylesheetParameter('foo');
		$this->assertSame('foo', $parameter->getValue());
	}
}