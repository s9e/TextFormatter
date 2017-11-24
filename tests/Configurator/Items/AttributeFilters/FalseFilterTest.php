<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\FalseFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\FalseFilter
*/
class FalseFilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\AttributeFilters\FalseFilter::filter()
	*/
	public function testCallback()
	{
		$filter = new FalseFilter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\FalseFilter::filter',
			$filter->getCallback()
		);
	}
}