<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\HashmapFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\HashmapFilter
*/
class HashmapFilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\AttributeFilters\HashmapFilter::filter()
	*/
	public function testCallback()
	{
		$filter = new HashmapFilter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\HashmapFilter::filter',
			$filter->getCallback()
		);
	}

	/**
	* @testdox __construct() forwards its arguments to setMap()
	*/
	public function testConstructorArguments()
	{
		$className = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\HashmapFilter';
 		$filter = $this->getMockBuilder($className)
		               ->setMethods(['setMap'])
		               ->getMock();

		$filter->expects($this->once())
		       ->method('setMap')
		       ->with(['one' => 'uno', 'two' => 'dos'], true);

		$filter->__construct(['one' => 'uno', 'two' => 'dos'], true);
	}

	/**
	* @testdox asConfig() throws an exception if the 'map' var is missing
	*/
	public function testMissingHashmap()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Hashmap filter is missing a 'map' value");

		$filter = new HashmapFilter;
		$filter->asConfig();
	}

	/**
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$filter = new HashmapFilter(['foo' => 'bar']);
		$this->assertIsArray($filter->asConfig());
	}

	/**
	* @testdox Creates a sparse map by default
	*/
	public function testSetHashmapStrict()
	{
		$filter = new HashmapFilter(['foo' => 'bar']);

		$vars = $filter->getVars();

		$this->assertFalse($vars['strict']);
	}

	/**
	* @testdox Creates a strict map if the second argument is TRUE
	*/
	public function testSetHashmapSparse()
	{
		$filter = new HashmapFilter(['foo' => 'bar'], true);

		$vars = $filter->getVars();

		$this->assertTrue($vars['strict']);
	}

	/**
	* @testdox Throws an exception if the second argument is not a boolean
	*/
	public function testStrictNotBool()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('must be a boolean');

		new HashmapFilter(['foo' => 'bar'], 'notbool');
	}

	/**
	* @testdox Values identical to their key are optimized away if the map is sparse
	*/
	public function testOptimizeSparse()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'bar' => 'bar']);

		$vars = $filter->getVars();

		$this->assertSame(['foo' => 'bar'], (array) $vars['map']);
	}

	/**
	* @testdox Values identical to their key are preserved if the map is strict
	*/
	public function testNotOptimizeStrict()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'bar' => 'bar', 'baz' => 'quux'], true);

		$vars = $filter->getVars();

		$this->assertEquals(['foo' => 'bar', 'bar' => 'bar', 'baz' => 'quux'], (array) $vars['map']);
	}

	/**
	* @testdox isSafeInCSS() returns false if the map is not strict
	*/
	public function testIsSafeInCSSNotStrict()
	{
		$filter = new HashmapFilter(['foo' => 'bar'], false);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns true if the map is strict
	*/
	public function testIsSafeInCSSStrict()
	{
		$filter = new HashmapFilter(['foo' => 'bar'], true);

		$this->assertTrue($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns false if a value in the map contains a parenthesis
	*/
	public function testIsSafeInCSSParenthesis()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'baz' => 'foo()'], true);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns false if a value in the map contains a colon
	*/
	public function testIsSafeInCSSColon()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'baz' => 'foo:bar'], true);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns false if a value in the map contains a semicolon
	*/
	public function testIsSafeInCSSSemicolon()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'baz' => 'foo;bar'], true);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInJS() returns false if the map is not strict
	*/
	public function testIsSafeInJSNotStrict()
	{
		$filter = new HashmapFilter(['foo' => 'bar'], false);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns true if the map is strict
	*/
	public function testIsSafeInJSStrict()
	{
		$filter = new HashmapFilter(['foo' => 'bar'], true);

		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains a parenthesis
	*/
	public function testIsSafeInJSParenthesis()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'baz' => 'foo()'], true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains a single quote
	*/
	public function testIsSafeInJSSingleQuote()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'baz' => "foo'bar"], true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains a double quote
	*/
	public function testIsSafeInJSDoubleQuote()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'baz' => 'foo"bar'], true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains \r or \n
	*/
	public function testIsSafeInJSNewLine()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'baz' => "foo\rbar"], true);
		$this->assertFalse($filter->isSafeInJS());

		$filter = new HashmapFilter(['foo' => 'bar', 'baz' => "foo\nbar"], true);
		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains U+2028
	*/
	public function testIsSafeInJSLineSeparator()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'baz' => "\xE2\x80\xA8"], true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains U+2029
	*/
	public function testIsSafeInJSParagraphSeparator()
	{
		$filter = new HashmapFilter(['foo' => 'bar', 'baz' => "\xE2\x80\xA9"], true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox Changing the map's content resets the filter's safeness
	*/
	public function testSetMapContentResetsSafeness()
	{
		$filter = new HashmapFilter(['foo' => 'bar'], true);
		$this->assertTrue($filter->isSafeInJS());

		$filter->setMap(['foo' => '"bar"'], true);
		$this->assertFalse($filter->isSafeInJS());

		$filter->setMap(['foo' => 'bar'], true);
		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox Changing the map's strictness resets the filter's safeness
	*/
	public function testSetMapStrictnessResetsSafeness()
	{
		$filter = new HashmapFilter(['foo' => 'bar'], true);
		$this->assertTrue($filter->isSafeInJS());

		$filter->setMap(['foo' => 'bar'], false);
		$this->assertFalse($filter->isSafeInJS());

		$filter->setMap(['foo' => 'bar'], true);
		$this->assertTrue($filter->isSafeInJS());
	}
}