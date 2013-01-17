<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Regexp;
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
		$filter->setVars(array('regexp' => ')invalid('));

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
	* @testdox Is safe in URL if the regexp is /^[a-z]$/D
	*/
	public function testURLSafeAlpha()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^[a-z]$/D');

		$this->assertTrue($filter->isSafeInURL());
	}

	/**
	* @testdox Is not safe in URL if the regexp is /^foo:$/
	*/
	public function testURLUnsafeColon()
	{
		$filter = new Regexp;
		$filter->setRegexp('/^foo:$/');

		$this->assertFalse($filter->isSafeInURL());
	}

	/**
	* @testdox Is not safe in URL if the regexp is invalid
	*/
	public function testURLUnsafeInvalidRegexp()
	{
		$filter = new Regexp;
		$filter->setVars(array('regexp' => ')invalid('));

		$this->assertFalse($filter->isSafeInURL());
	}

	/**
	* @testdox Is not safe in URL if no regexp is set
	*/
	public function testURLUnsafeNoRegexp()
	{
		$filter = new Regexp;

		$this->assertFalse($filter->isSafeInURL());
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
	* @testdox setRegexp() sets the filter's regexp
	*/
	public function testSetRegexp()
	{
		$filter = new Regexp;
		$filter->setRegexp('/x/');

		$this->assertSame(
			array('regexp' => '/x/'),
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