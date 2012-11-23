<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes\Configurator;

use s9e\TextFormatter\Plugins\BBCodes\Configurator\AttributeValueCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Configurator\AttributeValueCollection
*/
class AttributeValueCollectionTest extends Test
{
	/**
	* @testdox Throws an exception on invalid attribute name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid attribute name '*invalid*'
	*/
	public function testInvalidName()
	{
		$collection = new AttributeValueCollection;
		$collection->add('*invalid*', 1);
	}
}