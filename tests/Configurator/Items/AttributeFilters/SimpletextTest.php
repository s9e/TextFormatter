<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Simpletext;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Simpletext
*/
class SimpletextTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterSimpletext()
	*/
	public function testCallback()
	{
		$filter = new Simpletext;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterSimpletext',
			$filter->getCallback()
		);
	}

	/**
	* @testdox Is safe in CSS
	*/
	public function testIsSafeInCSS()
	{
		$filter = new Simpletext;
		$this->assertTrue($filter->isSafeInCSS());
	}
}