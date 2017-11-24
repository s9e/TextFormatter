<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\EmailFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\EmailFilter
*/
class EmailFilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\AttributeFilters\EmailFilter::filter()
	*/
	public function testCallback()
	{
		$filter = new EmailFilter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\EmailFilter::filter',
			$filter->getCallback()
		);
	}
}