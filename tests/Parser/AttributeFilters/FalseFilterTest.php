<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\FalseFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\FalseFilter
*/
class FalseFilterTest extends AbstractFilterTestClass
{
	public static function getFilterTests()
	{
		return [
			[new FalseFilter, 'bar', false],
			[new FalseFilter, 'false', false],
		];
	}
}