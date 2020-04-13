<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\FontfamilyFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\FontfamilyFilter
*/
class FontfamilyFilterTest extends Test
{
	/**
	* @testdox Is safe as URL
	*/
	public function testURLSafe()
	{
		$filter = new FontfamilyFilter;

		$this->assertTrue($filter->isSafeAsURL());
	}

	/**
	* @testdox Is safe in CSS
	*/
	public function testCSSSafe()
	{
		$filter = new FontfamilyFilter;

		$this->assertTrue($filter->isSafeInCSS());
	}

	/**
	* @testdox Is not safe in JS
	*/
	public function testJSUnsafe()
	{
		$filter = new FontfamilyFilter;

		$this->assertFalse($filter->isSafeInJS());
	}
}