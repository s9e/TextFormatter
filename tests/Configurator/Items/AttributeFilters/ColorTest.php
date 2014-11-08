<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Color;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Color
*/
class ColorTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterColor()
	*/
	public function testCallback()
	{
		$filter = new Color;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterColor',
			$filter->getCallback()
		);
	}

	/**
	* @testdox Is safe in CSS
	*/
	public function testIsSafeInCSS()
	{
		$filter = new Color;
		$this->assertTrue($filter->isSafeInCSS());
	}
}