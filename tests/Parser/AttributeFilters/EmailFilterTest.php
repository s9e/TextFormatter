<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\EmailFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\EmailFilter
*/
class EmailFilterTest extends AbstractFilterTestClass
{
	public static function getFilterTests()
	{
		return [
			[new EmailFilter, 'example@example.com', 'example@example.com'],
			[new EmailFilter, 'example@example.com()', false],
		];
	}
}