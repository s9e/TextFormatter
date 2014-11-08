<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipport;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipport
*/
class IpportTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterIpport()
	*/
	public function testCallback()
	{
		$filter = new Ipport;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterIpport',
			$filter->getCallback()
		);
	}
}