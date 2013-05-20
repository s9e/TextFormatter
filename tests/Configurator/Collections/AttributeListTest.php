<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\AttributeList;

/**
* @covers s9e\TextFormatter\Configurator\Collections\AttributeList
*/
class AttributeListTest extends Test
{
	/**
	* @testdox Attribute names are normalized for storage
	*/
	public function testNamesAreNormalizedForStorage()
	{
		$attributeList = new AttributeList;
		$attributeList->append('UrL');

		$this->assertTrue($attributeList->contains('url'));
	}

	/**
	* @testdox Attribute names are normalized during retrieval
	*/
	public function testNamesAreNormalizedDuringRetrieval()
	{
		$attributeList = new AttributeList;
		$attributeList->append('UrL');

		$this->assertTrue($attributeList->contains('uRl'));
	}

	/**
	* @testdox asConfig() returns a deduplicated list of attribute names
	*/
	public function testGetConfigDedupes()
	{
		$attributeList = new AttributeList;
		$attributeList->append('url');
		$attributeList->append('url');

		$this->assertSame(
			['url'],
			$attributeList->asConfig()
		);
	}
}