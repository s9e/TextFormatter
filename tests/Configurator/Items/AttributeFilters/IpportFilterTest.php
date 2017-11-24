<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\IpportFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\IpportFilter
*/
class IpportFilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\AttributeFilters\NetworkFilter::filterIpport()
	*/
	public function testCallback()
	{
		$filter = new IpportFilter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\NetworkFilter::filterIpport',
			$filter->getCallback()
		);
	}
}