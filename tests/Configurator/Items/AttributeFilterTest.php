<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilter
*/
class AttributeFilterTest extends Test
{
	/**
	* @testdox Sets the filter's signature to ['attrValue' => null]
	*/
	public function testDefaultSignature()
	{
		$filter = new AttributeFilter(function($v){});
		$config = $filter->asConfig();

		$this->assertSame(
			['attrValue' => null],
			$config['params']
		);
	}

	/**
	* @testdox isSafeInCSS() returns FALSE by default
	*/
	public function testNotSafeInCSS()
	{
		$filter = new AttributeFilter(function(){});
		$this->assertFalse($filter->isSafeInCSS());
	}

	/**
	* @testdox isSafeInJS() returns FALSE by default
	*/
	public function testNotSafeInJS()
	{
		$filter = new AttributeFilter(function(){});
		$this->assertFalse($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns TRUE if the callback is 'rawurlencode'
	*/
	public function testSafeInJSRawurlencode()
	{
		$filter = new AttributeFilter('rawurlencode');
		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns TRUE if the callback is 'strtotime'
	*/
	public function testSafeInJSStrtotime()
	{
		$filter = new AttributeFilter('strtotime');
		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeInJS() returns TRUE if the callback is 'urlencode'
	*/
	public function testSafeInJSUrlencode()
	{
		$filter = new AttributeFilter('urlencode');
		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox isSafeAsURL() returns FALSE by default
	*/
	public function testNotSafeInURL()
	{
		$filter = new AttributeFilter(function(){});
		$this->assertFalse($filter->isSafeAsURL());
	}
}