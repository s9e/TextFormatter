<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\MapFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\MapFilter
*/
class MapFilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\AttributeFilters\MapFilter::filter()
	*/
	public function testCallback()
	{
		$filter = new MapFilter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\MapFilter::filter',
			$filter->getCallback()
		);
	}

	/**
	* @testdox __construct() forwards its arguments to setMap()
	*/
	public function testConstructorArguments()
	{
		$className = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\MapFilter';
 		$filter = $this->getMockBuilder($className)
		               ->disableOriginalConstructor()
		               ->setMethods(['setMap'])
		               ->getMock();

		$filter->expects($this->once())
		       ->method('setMap')
		       ->with(['one' => 'uno', 'two' => 'dos'], true, true);

		$filter->__construct(['one' => 'uno', 'two' => 'dos'], true, true);
	}

	/**
	* @testdox asConfig() throws an exception if the 'map' var is missing
	*/
	public function testMissingMap()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Map filter is missing a 'map' value");

		$filter = new MapFilter;
		$filter->asConfig();
	}

	/**
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$filter = new MapFilter(['foo' => 'bar']);
		$this->assertIsArray($filter->asConfig());
	}

	/**
	* @testdox setMap() creates case-insensitive regexps by default
	*/
	public function testSetMapCaseInsensitive()
	{
		$filter = new MapFilter;
		$filter->setMap(['foo' => 'bar']);

		$vars = $filter->getVars();

		$this->assertSame(
			'/^foo$/Di',
			(string) $vars['map'][0][0]
		);
	}

	/**
	* @testdox setMap() creates case-sensitive regexps if its second argument is TRUE
	*/
	public function testSetMapCaseSensitive()
	{
		$filter = new MapFilter;
		$filter->setMap(['foo' => 'bar'], true);

		$vars = $filter->getVars();

		$this->assertSame(
			'/^foo$/D',
			(string) $vars['map'][0][0]
		);
	}

	/**
	* @testdox setMap() appends a catch-all regexp that maps to FALSE if its third argument is TRUE
	*/
	public function testSetMapStrict()
	{
		$filter = new MapFilter;
		$filter->setMap(['foo' => 'bar'], false, true);

		$vars = $filter->getVars();

		$this->assertSame(
			'//',
			(string) $vars['map'][1][0]
		);
		$this->assertFalse($vars['map'][1][1]);
	}

	/**
	* @testdox setMap() uses the pattern modifier 'u' if a regexp is not entirely ASCII
	*/
	public function testSetMapUnicode()
	{
		$filter = new MapFilter;
		$filter->setMap(['pokémon' => 'yugioh']);

		$vars = $filter->getVars();

		$this->assertSame(
			'/^pokémon$/Diu',
			(string) $vars['map'][0][0]
		);
	}

	/**
	* @testdox setMap() throws an exception if the second argument is not a boolean
	*/
	public function testSetMapNotBool2()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('must be a boolean');

		$filter = new MapFilter;
		$filter->setMap(['foo' => 'bar'], 'notbool');
	}

	/**
	* @testdox setMap() throws an exception if the third argument is not a boolean
	*/
	public function testSetMapNotBool3()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('must be a boolean');

		$filter = new MapFilter;
		$filter->setMap(['foo' => 'bar'], true, 'notbool');
	}

	/**
	* @testdox isSafeInCSS() returns false if the map is not strict
	*/
	public function testIsSafeInCSSNotStrict()
	{
		$filter = new MapFilter(['foo' => 'bar'], true, false);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns true if the map is strict
	*/
	public function testIsSafeInCSSStrict()
	{
		$filter = new MapFilter(['foo' => 'bar'], true, true);

		$this->assertTrue($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns false if a safe map is replaced with an unsafe map state
	*/
	public function testIsSafeInCSSReset()
	{
		$filter = new MapFilter(['foo' => 'bar'], true, true);
		$filter->setMap(['foo' => 'bar'], true, false);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns false if a value in the map contains a parenthesis
	*/
	public function testIsSafeInCSSParenthesis()
	{
		$filter = new MapFilter(['foo' => 'bar', 'baz' => 'foo()'], true, true);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns false if a value in the map contains a colon
	*/
	public function testIsSafeInCSSColon()
	{
		$filter = new MapFilter(['foo' => 'bar', 'baz' => 'foo:bar'], true, true);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns false if a value in the map contains a semicolon
	*/
	public function testIsSafeInCSSSemicolon()
	{
		$filter = new MapFilter(['foo' => 'bar', 'baz' => 'foo;bar'], true, true);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInJS() returns false if the map is not strict
	*/
	public function testIsSafeInJSNotStrict()
	{
		$filter = new MapFilter(['foo' => 'bar'], true, false);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns true if the map is strict
	*/
	public function testIsSafeInJSStrict()
	{
		$filter = new MapFilter(['foo' => 'bar'], true, true);

		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a safe map is replaced with an unsafe map state
	*/
	public function testIsSafeInJSReset()
	{
		$filter = new MapFilter(['foo' => 'bar'], true, true);
		$filter->setMap(['foo' => 'bar'], true, false);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains a parenthesis
	*/
	public function testIsSafeInJSParenthesis()
	{
		$filter = new MapFilter(['foo' => 'bar', 'baz' => 'foo()'], true, true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains a single quote
	*/
	public function testIsSafeInJSSingleQuote()
	{
		$filter = new MapFilter(['foo' => 'bar', 'baz' => "foo'bar"], true, true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains a double quote
	*/
	public function testIsSafeInJSDoubleQuote()
	{
		$filter = new MapFilter(['foo' => 'bar', 'baz' => 'foo"bar'], true, true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains \r or \n
	*/
	public function testIsSafeInJSNewLine()
	{
		$filter = new MapFilter(['foo' => 'bar', 'baz' => "foo\rbar"], true, true);
		$this->assertFalse($filter->isSafeInJS());

		$filter = new MapFilter(['foo' => 'bar', 'baz' => "foo\nbar"], true, true);
		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains U+2028
	*/
	public function testIsSafeInJSLineSeparator()
	{
		$filter = new MapFilter(['foo' => 'bar', 'baz' => "\xE2\x80\xA8"], true, true);

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns false if a value in the map contains U+2029
	*/
	public function testIsSafeInJSParagraphSeparator()
	{
		$filter = new MapFilter(['foo' => 'bar', 'baz' => "\xE2\x80\xA9"], true, true);

		$this->assertFalse($filter->isSafeInJS());
	}
}