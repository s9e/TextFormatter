<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\RangeFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\NumericFilter
*/
class RangeFilterTest extends AbstractFilterTestClass
{
	public static function getFilterTests()
	{
		return [
			[new RangeFilter(2, 5), '2', 2],
			[new RangeFilter(2, 5), '5', 5],
			[new RangeFilter(-5, 5), '-5', -5],
			[
				new RangeFilter(2, 5),
				'1',
				2,
				[
					[
						'warn',
						'Value outside of range, adjusted up to min value',
						['attrValue' => 1, 'min' => 2, 'max' => 5]
					]
				]
			],
			[
				new RangeFilter(2, 5),
				'10',
				5,
				[
					[
						'warn',
						'Value outside of range, adjusted down to max value',
						['attrValue' => 10, 'min' => 2, 'max' => 5]
					]
				]
			],
			[new RangeFilter(2, 5), '5x', false],
		];
	}
}