<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use Exception;
use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\AttributeCollection;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\AttributeCollection
*/
class AttributeCollectionTest extends Test
{
	/**
	* @testdox add() returns an instance of s9e\TextFormatter\ConfigBuilder\Items\Attribute
	*/
	public function testClassName()
	{
		$collection = new AttributeCollection;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\Attribute',
			$collection->add('x')
		);
	}
}