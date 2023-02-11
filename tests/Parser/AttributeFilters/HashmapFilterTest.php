<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\HashmapFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\HashmapFilter
*/
class HashmapFilterTest extends AbstractFilterTestClass
{
	public static function getFilterTests()
	{
		return [
			[new HashmapFilter(['foo' => 'bar']), 'foo', 'bar'],
			[new HashmapFilter(['foo' => 'bar']), 'bar', 'bar'],
			[new HashmapFilter(['foo' => 'bar'], false), 'bar', 'bar'],
			[new HashmapFilter(['foo' => 'bar'], true), 'bar', false],
		];
	}
}