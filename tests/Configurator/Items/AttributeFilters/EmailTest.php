<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Email;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Email
*/
class EmailTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterEmail()
	*/
	public function testCallback()
	{
		$filter = new Email;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterEmail',
			$filter->getCallback()
		);
	}
}