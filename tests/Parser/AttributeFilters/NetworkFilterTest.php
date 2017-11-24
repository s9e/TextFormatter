<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\IpFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\IpportFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv4Filter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv6Filter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\NetworkFilter
*/
class NetworkFilterTest extends AbstractFilterTest
{
	public function getFilterTests()
	{
		return [
			[new IpFilter, '8.8.8.8', '8.8.8.8'],
			[new IpFilter, 'ff02::1', 'ff02::1'],
			[new IpFilter, 'localhost', false],
			[new Ipv4Filter, '8.8.8.8', '8.8.8.8'],
			[new Ipv4Filter, 'ff02::1', false],
			[new Ipv4Filter, 'localhost', false],
			[new Ipv6Filter, '8.8.8.8', false],
			[new Ipv6Filter, 'ff02::1', 'ff02::1'],
			[new Ipv6Filter, 'localhost', false],
			[new IpportFilter, '8.8.8.8:80', '8.8.8.8:80'],
			[new IpportFilter, '[ff02::1]:80', '[ff02::1]:80'],
			[new IpportFilter, 'localhost:80', false],
			[new IpportFilter, '[localhost]:80', false],
			[new IpportFilter, '8.8.8.8', false],
			[new IpportFilter, 'ff02::1', false],
		];
	}
}