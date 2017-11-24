<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\ColorFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\RegexpFilter
*/
class ColorFilterTest extends AbstractFilterTest
{
	public function getFilterTests()
	{
		return [
			[new ColorFilter, '#123abc', '#123abc'],
			[new ColorFilter, 'red', 'red'],
			[new ColorFilter, 'rgb(12,34,56)', 'rgb(12,34,56)'],
			[new ColorFilter, 'rgb(12, 34, 56)', 'rgb(12, 34, 56)'],
			[new ColorFilter, '#1234567', false],
			[new ColorFilter, 'blue()', false],
		];
	}
}