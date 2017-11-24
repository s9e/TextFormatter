<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\AlnumFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\RegexpFilter
*/
class AlnumFilterTest extends AbstractFilterTest
{
	public function getFilterTests()
	{
		return [
			[new AlnumFilter, '', false],
			[new AlnumFilter, 'abcDEF', 'abcDEF'],
			[new AlnumFilter, 'abc_def', false],
			[new AlnumFilter, '0123', '0123'],
			[new AlnumFilter, 'é', false],
		];
	}
}