<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Alnum;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Alnum
*/
class AlnumTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterAlnum()
	*/
	public function testCallback()
	{
		$filter = new Alnum;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterAlnum',
			$filter->getCallback()
		);
	}

	/**
	* @testdox Is safe in CSS
	*/
	public function testIsSafeInCSS()
	{
		$filter = new Alnum;
		$this->assertTrue($filter->isSafeInCSS());
	}

	/**
	* @testdox Is safe in URL
	*/
	public function testIsSafeInURL()
	{
		$filter = new Alnum;
		$this->assertTrue($filter->isSafeAsURL());
	}
}