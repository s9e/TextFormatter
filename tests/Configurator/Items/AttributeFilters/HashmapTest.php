<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Hashmap;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Hashmap
*/
class HashmapTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterHashmap()
	*/
	public function testCallback()
	{
		$filter = new Hashmap;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterHashmap',
			$filter->getCallback()
		);
	}

	/**
	* @testdox __construct() forwards its arguments to setMap()
	*/
	public function testConstructorArguments()
	{
		$className = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\Hashmap';
		$filter = $this->getMockBuilder($className)
		               ->disableOriginalConstructor()
		               ->getMock();

		$filter->expects($this->once())
		       ->method('setMap')
		       ->with(array('one' => 'uno', 'two' => 'dos'), true);

		$filter->__construct(array('one' => 'uno', 'two' => 'dos'), true);
	}

	/**
	* @testdox asConfig() throws an exception if the 'map' var is missing
	* @expectedException RuntimeException
	* @expectedExceptionMessage Hashmap filter is missing a 'map' value
	*/
	public function testMissingHashmap()
	{
		$filter = new Hashmap;
		$filter->asConfig();
	}

	/**
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$filter = new Hashmap(array('foo' => 'bar'));
		$this->assertInternalType('array', $filter->asConfig());
	}

	/**
	* @testdox Creates a sparse map by default
	*/
	public function testSetHashmapStrict()
	{
		$filter = new Hashmap(array('foo' => 'bar'));

		$vars = $filter->getVars();

		$this->assertFalse($vars['strict']);
	}

	/**
	* @testdox Creates a strict map if the second argument is TRUE
	*/
	public function testSetHashmapSparse()
	{
		$filter = new Hashmap(array('foo' => 'bar'), true);

		$vars = $filter->getVars();

		$this->assertTrue($vars['strict']);
	}

	/**
	* @testdox Throws an exception if the second argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage must be a boolean
	*/
	public function testStrictNotBool()
	{
		new Hashmap(array('foo' => 'bar'), 'notbool');
	}

	/**
	* @testdox Values identical to their key are optimized away if the map is sparse
	*/
	public function testOptimizeSparse()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'bar' => 'bar'));

		$vars = $filter->getVars();

		$this->assertSame(array('foo' => 'bar'), $vars['map']);
	}

	/**
	* @testdox Values identical to their key are preserved if the map is strict
	*/
	public function testNotOptimizeStrict()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'bar' => 'bar', 'baz' => 'quux'), true);

		$vars = $filter->getVars();

		$this->assertEquals(array('foo' => 'bar', 'bar' => 'bar', 'baz' => 'quux'), $vars['map']);
	}

	/**
	* @testdox isSafeInCSS() returns false if the map is not strict
	*/
	public function testIsSafeInCSSNotStrict()
	{
		$filter = new Hashmap(array('foo' => 'bar'), false);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns true if the map is strict
	*/
	public function testIsSafeInCSSStrict()
	{
		$filter = new Hashmap(array('foo' => 'bar'), true);

		$this->assertTrue($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns false if a value in the map contains a parenthesis
	*/
	public function testIsSafeInCSSParenthesis()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'baz' => 'foo()'), true);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns false if a value in the map contains a colon
	*/
	public function testIsSafeInCSSColon()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'baz' => 'foo:bar'), true);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns false if a value in the map contains a semicolon
	*/
	public function testIsSafeInCSSSemicolon()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'baz' => 'foo;bar'), true);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInJS() returns false if the map is not strict
	*/
	public function testIsSafeInJSNotStrict()
	{
		$filter = new Hashmap(array('foo' => 'bar'), false);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns true if the map is strict
	*/
	public function testIsSafeInJSStrict()
	{
		$filter = new Hashmap(array('foo' => 'bar'), true);

		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains a parenthesis
	*/
	public function testIsSafeInJSParenthesis()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'baz' => 'foo()'), true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains a single quote
	*/
	public function testIsSafeInJSSingleQuote()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'baz' => "foo'bar"), true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains a double quote
	*/
	public function testIsSafeInJSDoubleQuote()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'baz' => 'foo"bar'), true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains \r or \n
	*/
	public function testIsSafeInJSNewLine()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'baz' => "foo\rbar"), true);
		$this->assertFalse($filter->isSafeInJS());

		$filter = new Hashmap(array('foo' => 'bar', 'baz' => "foo\nbar"), true);
		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains U+2028
	*/
	public function testIsSafeInJSLineSeparator()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'baz' => "\xE2\x80\xA8"), true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains U+2029
	*/
	public function testIsSafeInJSParagraphSeparator()
	{
		$filter = new Hashmap(array('foo' => 'bar', 'baz' => "\xE2\x80\xA9"), true);

		$this->assertFalse($filter->isSafeInJS());
	}
}