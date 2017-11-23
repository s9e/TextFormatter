<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\ColorFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\ColorFilter
*/
class ColorFilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterRegexp()
	*/
	public function testCallback()
	{
		$filter = new ColorFilter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterRegexp',
			$filter->getCallback()
		);
	}

	/**
	* @testdox Is safe in CSS
	*/
	public function testIsSafeInCSS()
	{
		$filter = new ColorFilter;
		$this->assertTrue($filter->isSafeInCSS());
	}
}