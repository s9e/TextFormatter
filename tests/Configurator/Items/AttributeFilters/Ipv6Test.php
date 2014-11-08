<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv6;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv6
*/
class Ipv6Test extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterIpv6()
	*/
	public function testCallback()
	{
		$filter = new Ipv6;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterIpv6',
			$filter->getCallback()
		);
	}
}