<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Float;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Float
*/
class FloatTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterFloat()
	*/
	public function testCallback()
	{
		$filter = new Float;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterFloat',
			$filter->getCallback()
		);
	}

	/**
	* @testdox Is safe in CSS
	*/
	public function testIsSafeInCSS()
	{
		$filter = new Float;
		$this->assertTrue($filter->isSafeInCSS());
	}

	/**
	* @testdox Is safe in JS
	*/
	public function testIsSafeInJS()
	{
		$filter = new Float;
		$this->assertTrue($filter->isSafeInJS());
	}

	/**
	* @testdox Is safe in URL
	*/
	public function testIsSafeInURL()
	{
		$filter = new Float;
		$this->assertTrue($filter->isSafeAsURL());
	}
}