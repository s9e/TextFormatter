<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv4Filter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv4Filter
*/
class Ipv4FilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\AttributeFilters\NetworkFilter::filterIpv4()
	*/
	public function testCallback()
	{
		$filter = new Ipv4Filter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\NetworkFilter::filterIpv4',
			$filter->getCallback()
		);
	}
}