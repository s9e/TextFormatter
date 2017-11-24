<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv6Filter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv6Filter
*/
class Ipv6FilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\AttributeFilters\NetworkFilter::filterIpv6()
	*/
	public function testCallback()
	{
		$filter = new Ipv6Filter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\NetworkFilter::filterIpv6',
			$filter->getCallback()
		);
	}
}