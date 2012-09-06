<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Items;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Items\Attribute;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Items\Attribute
*/
class AttributeTest extends Test
{
	/**
	* @testdox The filterChain property can assigned an array
	*/
	public function testSetFilterChainArray()
	{
		$attr = new Attribute;
		$attr->filterChain = array('#int', '#url');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Collections\\FilterChain',
			$attr->filterChain
		);

		$this->assertSame(2, count($attr->filterChain), 'Wrong filter count');
	}
}