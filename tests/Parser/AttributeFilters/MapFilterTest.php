<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\MapFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\MapFilter
*/
class MapFilterTest extends AbstractFilterTest
{
	public function getFilterTests()
	{
		return [
			[new MapFilter(['uno' => 'one', 'dos' => 'two']), 'dos', 'two'],
			[new MapFilter(['uno' => 'one', 'dos' => 'two']), 'three', 'three'],
			[new MapFilter(['uno' => 'one', 'dos' => 'two'], true, true), 'three', false],
		];
	}
}