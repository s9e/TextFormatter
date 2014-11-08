<?php

namespace s9e\TextFormatter\Tests\Configurator\Items\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ip;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\AttributeFilters\Ip
*/
class IpTest extends Test
{
	/**
	* @testdox Callback is s9e\TextFormatter\Parser\BuiltInFilters::filterIp()
	*/
	public function testCallback()
	{
		$filter = new Ip;

		$this->assertSame(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterIp',
			$filter->getCallback()
		);
	}
}