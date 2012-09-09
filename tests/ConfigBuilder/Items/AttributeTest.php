<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Items;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\FilterChain;
use s9e\TextFormatter\ConfigBuilder\Items\Attribute;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Items\Attribute
*/
class AttributeTest extends Test
{
	/**
	* @testdox An array of options can be passed to the constructor
	*/
	public function testConstructorOptions()
	{
		$attr = new Attribute(array('isRequired' => false));
		$this->assertFalse($attr->isRequired);

		$attr = new Attribute(array('isRequired' => true));
		$this->assertTrue($attr->isRequired);
	}

	/**
	* @testdox $attr->filterChain can be assigned an array
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

	/**
	* @testdox $attr->filterChain can be assigned an instance of FilterChain to copy its content
	*/
	public function testSetFilterChainInstance()
	{
		$filterChain = new FilterChain(array('attrVal' => null));
		$filterChain->append('strtolower');

		$attr = new Attribute;
		$attr->filterChain = $filterChain;

		$this->assertEquals($filterChain, $attr->filterChain);
		$this->assertNotSame($filterChain, $attr->filterChain, '$attr->filterChain should not have been replaced with $filterChain');
	}

	/**
	* @testdox setFilterChain() throws an InvalidArgumentException if its argument is not an array or an instance of FilterChain
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage setFilterChain() expects an array or an instance of FilterChain
	*/
	public function testSetFilterChainInvalid()
	{
		$attr = new Attribute;
		$attr->filterChain = false;
	}
}