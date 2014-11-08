<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv4;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv4
*/
class Ipv4Test extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterIpv4()
	*/
	public function testCallback()
	{
		$filter = new Ipv4;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterIpv4',
			$filter->getCallback()
		);
	}
}