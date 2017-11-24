<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\IpFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\IpFilter
*/
class IpFilterTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\AttributeFilters\NetworkFilter::filterIp()
	*/
	public function testCallback()
	{
		$filter = new IpFilter;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\NetworkFilter::filterIp',
			$filter->getCallback()
		);
	}
}