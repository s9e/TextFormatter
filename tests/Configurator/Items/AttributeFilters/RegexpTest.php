<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Regexp;
use s9e\TextFormatter\Configurator\Items\Regexp as RegexpObject;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Regexp
*/
class RegexpTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterRegexp()
	*/
	public function testCallback()
	{
		$filter = new Regexp;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterRegexp',
			$filter->getCallback()
		);
	}

	/**
	* @testdox Is not safe as URL
	*/
	public function testURLUnsafeNoRegexp()
	{
		$filter = new Regexp;

		$this->assertFalse($filter->isSafeAsURL());
	}

	/**
	* @testdox Is safe as URL if the regexp is /^[a-z]$/
	*/
	public function testURLSafeAlpha()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^[a-z]$/');

		$this->assertTrue($filter->isSafeAsURL());
	}

	/**
	* @testdox Is safe as URL if the regexp is /^foo:(.*)/
	*/
	public function testURLSafeFixedScheme()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^foo:(.*)/');

		$this->assertTrue($filter->isSafeAsURL());
	}

	/**
	* @testdox Is not safe as URL if the regexp is /^foo:(.*)/m
	*/
	public function testURLUnsafeFixedSchemeMultiline()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^foo:(.*)/m');

		$this->assertFalse($filter->isSafeAsURL());
	}

	/**
	* @testdox Is safe as URL if the regexp is /^https?:(.*)/
	*/
	public function testURLSafeFixedSchemeWithJoker()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^https?:(.*)/');

		$this->assertTrue($filter->isSafeAsURL());
	}

	/**
	* @testdox Is safe as URL if the regexp is /^(https?:.*)/
	*/
	public function testURLSafeCapturedFixedSchemeWithJoker()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^(https?:.*)/');

		$this->assertTrue($filter->isSafeAsURL());
	}

	/**
	* @testdox Is safe as URL if the regexp is /^(?:https?:.*)/
	*/
	public function testURLSafeNonCapturingFixedSchemeWithJoker()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^(?:https?:.*)/');

		$this->assertTrue($filter->isSafeAsURL());
	}

	/**
	* @testdox Is not safe as URL if the regexp is /^javascript:(.*)/
	*/
	public function testURLUnsafeJavaScriptScheme()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^javascript:(.*)/');

		$this->assertFalse($filter->isSafeAsURL());
	}

	/**
	* @testdox Is not safe as URL if the regexp is /^javascriptx?:(.*)/
	*/
	public function testURLUnsafeJavaScriptSchemeWithJoker()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^javascriptx?:(.*)/');

		$this->assertFalse($filter->isSafeAsURL());
	}

	/**
	* @testdox Is not safe as URL if the regexp allows a colon to be used
	*/
	public function testURLUnsafeColon()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^:$/');

		$this->assertFalse($filter->isSafeAsURL());
	}

	/**
	* @testdox Is not safe as URL if the regexp is invalid
	*/
	public function testURLUnsafeInvalidRegexp()
	{
		$filter = new Regexp;
		$filter->setVars(['regexp' => ')invalid(']);

		$this->assertFalse($filter->isSafeAsURL());
	}

	/**
	* @testdox Is safe in CSS if the regexp is /^(?:left|right|center)$/
	*/
	public function testCSSSafe()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^(?:left|right|center)$/');

		$this->assertTrue($filter->isSafeInCSS());
	}

	/**
	* @testdox Is safe in CSS if the regexp is /^[a-z]$/
	*/
	public function testCSSSafeAlpha()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^[a-z]$/');

		$this->assertTrue($filter->isSafeInCSS());
	}

	/**
	* @testdox Is not safe in CSS if the regexp is /^[A-z]$/ because it would allow backslashes
	*/
	public function testCSSUnsafeCharacterClass()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^[A-z]$/');

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox Is not safe in CSS if the regexp is invalid
	*/
	public function testCSSUnsafeInvalidRegexp()
	{
		$filter = new Regexp;
		$filter->setVars(['regexp' => ')invalid(']);

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox Is not safe in CSS if no regexp is set
	*/
	public function testCSSUnsafeNoRegexp()
	{
		$filter = new Regexp;

		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox Is safe in JS if the regexp is /^\d+$/D
	*/
	public function testJSSafe1()
	{
		$filter = new Regexp('/^\\d+$/D');

		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox Is safe in JS if the regexp is /^[0-9]+$/D
	*/
	public function testJSSafe2()
	{
		$filter = new Regexp('/^[0-9]+$/D');

		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox Is safe in JS if the regexp is /^(?:[0-9]+)$/D
	*/
	public function testJSSafe3()
	{
		$filter = new Regexp('/^(?:[0-9]+)$/D');

		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox Is not safe in JS if the regexp is /^\d+/D
	*/
	public function testJSUnsafeUnanchoredStart()
	{
		$filter = new Regexp('/^\\d+/D');

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox Is not safe in JS if the regexp is /\d+$/D
	*/
	public function testJSUnsafeUnanchoredEnd()
	{
		$filter = new Regexp('/\\d+$/D');

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox Is not safe in JS if the regexp is /^\d+$/
	*/
	public function testJSUnsafeNoDollarEndOnly()
	{
		$filter = new Regexp('/^\\d+$/');

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox Is not safe in JS if the regexp is /^\d+$/Dm
	*/
	public function testJSUnsafeMultiLine()
	{
		$filter = new Regexp('/^\\d+$/Dm');

		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox __construct() forwards its arguments to setRegexp()
	*/
	public function testConstructorArguments()
	{
		$className = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\Regexp';
		$filter = $this->getMockBuilder($className)
		               ->disableOriginalConstructor()
		               ->getMock();

		$filter->expects($this->once())
		       ->method('setRegexp')
		       ->with('/foo/');

		$filter->__construct('/foo/');
	}

	/**
	* @testdox getRegexp() returns the filter's regexp
	*/
	public function testGetRegexp()
	{
		$filter = new Regexp('/foo/');

		$this->assertSame('/foo/', $filter->getRegexp());
	}

	/**
	* @testdox setRegexp() sets the filter's regexp
	*/
	public function testSetRegexp()
	{
		$filter = new Regexp;
		$filter->setRegexp('/x/');

		$this->assertEquals(
			['regexp' => '/x/'],
			$filter->getVars()
		);
	}

	/**
	* @testdox setRegexp() throws an exception if the regexp is invalid
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid regular expression '???'
	*/
	public function testSetRegexpInvalid()
	{
		$filter = new Regexp;
		$filter->setRegexp('???');
	}

	/**
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$filter = new Regexp;
		$filter->setRegexp('/x/');

		$this->assertInternalType('array', $filter->asConfig());
	}

	/**
	* @testdox asConfig() throws an exception if the 'regexp' var is missing
	* @expectedException RuntimeException
	* @expectedExceptionMessage Regexp filter is missing a 'regexp' value
	*/
	public function testMissingRegexp()
	{
		$filter = new Regexp;
		$filter->asConfig();
	}

	/**
	* @testdox asConfig() creates a JS variant for the regexp
	*/
	public function testAsConfigVariant()
	{
		$filter = new Regexp;
		$filter->setRegexp('/x/');

		$config = $filter->asConfig();
		$slice  = array_slice($config['params'], 1, 1);
		$variant = end($slice);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$variant
		);
		$this->assertTrue($variant->has('JS'));
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\RegExp',
			$variant->get('JS')
		);
	}
}