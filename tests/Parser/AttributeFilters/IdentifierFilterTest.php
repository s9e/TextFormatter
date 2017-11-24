<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\IdentifierFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\RegexpFilter
*/
class IdentifierFilterTest extends AbstractFilterTest
{
	public function getFilterTests()
	{
		return [
			[new IdentifierFilter, '123abcABC', '123abcABC'],
			[new IdentifierFilter, '-_-', '-_-'],
			[new IdentifierFilter, 'a b', false],
		];
	}
}