<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\FontfamilyFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\RegexpFilter
*/
class FontfamilyFilterTest extends AbstractFilterTest
{
	public function getFilterTests()
	{
		return [
			[new FontfamilyFilter, 'Arial', 'Arial'],
			[new FontfamilyFilter, '"Arial"', '"Arial"'],
			[new FontfamilyFilter, '"Arial""Arial"', false],
			[new FontfamilyFilter, 'Arial,serif', 'Arial,serif'],
			[new FontfamilyFilter, 'Arial, serif, sans-serif', 'Arial, serif, sans-serif'],
			[new FontfamilyFilter, 'Arial, Times New Roman', 'Arial, Times New Roman'],
			[new FontfamilyFilter, 'Arial, "Times New Roman"', 'Arial, "Times New Roman"'],
			[new FontfamilyFilter, "Arial, 'Times New Roman'", "Arial, 'Times New Roman'"],
			[new FontfamilyFilter, 'url(whatever)', false],
		];
	}
}