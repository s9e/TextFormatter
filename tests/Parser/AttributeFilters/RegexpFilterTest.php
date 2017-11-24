<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\RegexpFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\RegexpFilter
*/
class RegexpFilterTest extends AbstractFilterTest
{
	public function getFilterTests()
	{
		return [
			[new RegexpFilter('/^[A-Z]+$/D'), 'ABC', 'ABC'],
			[new RegexpFilter('/^[A-Z]+$/D'), 'Abc', false],
		];
	}
}