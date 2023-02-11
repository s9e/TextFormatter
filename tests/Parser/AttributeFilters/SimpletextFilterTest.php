<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\SimpletextFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\RegexpFilter
*/
class SimpletextFilterTest extends AbstractFilterTestClass
{
	public static function getFilterTests()
	{
		return [
			[
				new SimpletextFilter,
				'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+.,_ ', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+.,_ '
			],
			[new SimpletextFilter, 'a()b', false],
			[new SimpletextFilter, 'a[]b', false],
		];
	}
}