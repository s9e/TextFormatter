<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Int;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Url;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\Attribute
*/
class AttributeTest extends Test
{
	/**
	* @testdox An array of options can be passed to the constructor
	*/
	public function testConstructorOptions()
	{
		$attr = new Attribute(['isRequired' => false]);
		$this->assertFalse($attr->isRequired);

		$attr = new Attribute(['isRequired' => true]);
		$this->assertTrue($attr->isRequired);
	}

	/**
	* @testdox $attr->filterChain can be assigned an array
	*/
	public function testSetFilterChainArray()
	{
		$attr = new Attribute;
		$attr->filterChain = [new Int, new Url];

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\AttributeFilterChain',
			$attr->filterChain
		);

		$this->assertSame(2, count($attr->filterChain), 'Wrong filter count');
	}

	/**
	* @testdox $attr->generator accepts an instance of ProgrammableCallback
	*/
	public function testSetGeneratorProgrammableCallback()
	{
		$attr     = new Attribute;
		$callback = new ProgrammableCallback('mt_rand');

		$attr->generator = $callback;

		$this->assertSame($callback, $attr->generator);
	}

	/**
	* @testdox $attr->generator accepts a callback and normalizes it to an instance of ProgrammableCallback
	*/
	public function testSetGeneratorCallback()
	{
		$attr = new Attribute;
		$attr->generator = 'mt_rand';

		$this->assertInstanceof(
			's9e\\TextFormatter\\Configurator\\Items\\ProgrammableCallback',
			$attr->generator
		);
	}

	/**
	* @testdox asConfig() correctly produces a config array
	*/
	public function testAsConfig()
	{
		$attr = new Attribute;
		$attr->defaultValue = 'foo';

		$this->assertEquals(
			[
				'defaultValue' => 'foo',
				'required'     => true
			],
			$attr->asConfig()
		);
	}

	/**
	* @testdox isSafeAsURL() returns FALSE by default
	*/
	public function testIsSafeAsURLDefault()
	{
		$attr = new Attribute;
		$this->assertFalse($attr->isSafeAsURL());
	}

	/**
	* @testdox isSafeAsURL() returns TRUE if any filter is safe in context
	*/
	public function testIsSafeAsURLFilter()
	{
		$attr = new Attribute;
		$attr->filterChain->append(new DummyURLFilter);
		$this->assertTrue($attr->isSafeAsURL());
	}

	/**
	* @testdox markAsSafeAsURL() unconditionally marks the attribute as safe in context
	*/
	public function testMarkAsSafeAsURL()
	{
		$attr = new Attribute;
		$attr->markAsSafeAsURL();
		$this->assertTrue($attr->isSafeAsURL());
	}

	/**
	* @testdox isSafeInCSS() returns FALSE by default
	*/
	public function testIsSafeInCSSDefault()
	{
		$attr = new Attribute;
		$this->assertFalse($attr->isSafeInCSS());
	}

	/**
	* @testdox isSafeInCSS() returns TRUE if any filter is safe in context
	*/
	public function testIsSafeInCSSFilter()
	{
		$attr = new Attribute;
		$attr->filterChain->append(new DummyCSSFilter);
		$this->assertTrue($attr->isSafeInCSS());
	}

	/**
	* @testdox markAsSafeInCSS() unconditionally marks the attribute as safe in context
	*/
	public function testMarkAsSafeInCSS()
	{
		$attr = new Attribute;
		$attr->markAsSafeInCSS();
		$this->assertTrue($attr->isSafeInCSS());
	}

	/**
	* @testdox isSafeInJS() returns FALSE by default
	*/
	public function testIsSafeInJSDefault()
	{
		$attr = new Attribute;
		$this->assertFalse($attr->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns TRUE if any filter is safe in context
	*/
	public function testIsSafeInJSFilter()
	{
		$attr = new Attribute;
		$attr->filterChain->append(new DummyJSFilter);
		$this->assertTrue($attr->isSafeInJS());
	}

	/**
	* @testdox markAsSafeInJS() unconditionally marks the attribute as safe in context
	*/
	public function testMarkAsSafeInJS()
	{
		$attr = new Attribute;
		$attr->markAsSafeInJS();
		$this->assertTrue($attr->isSafeInJS());
	}
}

class DummyCSSFilter extends AttributeFilter
{
	public function __construct() {}
	public function isSafeInCSS()
	{
		return true;
	}
}

class DummyJSFilter extends AttributeFilter
{
	public function __construct() {}
	public function isSafeInJS()
	{
		return true;
	}
}

class DummyURLFilter extends AttributeFilter
{
	public function __construct() {}
	public function isSafeAsURL()
	{
		return true;
	}
}