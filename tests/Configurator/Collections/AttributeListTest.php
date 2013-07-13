<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\AttributeList;
use s9e\TextFormatter\Tests\Test;

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
	public function testAsConfigDedupes()
	{
		$attributeList = new AttributeList;
		$attributeList->append('url');
		$attributeList->append('url');

		$this->assertSame(
			['url'],
			$attributeList->asConfig()
		);
	}

	/**
	* @testdox asConfig() returns a list of attribute names in alphabetical order
	*/
	public function testAsConfigSort()
	{
		$attributeList = new AttributeList;
		$attributeList->append('url');
		$attributeList->append('title');
		$attributeList->append('width');

		$this->assertSame(
			['title', 'url', 'width'],
			$attributeList->asConfig()
		);
	}
}