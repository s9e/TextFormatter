<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use Exception,
    s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\Collections\AttributeCollection;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\AttributeCollection
*/
class AttributeCollectionTest extends Test
{
	/**
	* @dataProvider getAttributeNamesInitials
	*/
	public function testAttributeNamesMustStartWithALetterOrAnUnderscore($attrName, $expectedValue)
	{
		$collection = new AttributeCollection;

		$this->assertSame(
			$expectedValue,
			$collection->isValidName($attrName)
		);
	}

	public function getAttributeNamesInitials()
	{
		return array(
			array('aaa',  true),
			array('_foo', true),
			array('5aa',  false),
			array('XXX',  true)
		);
	}

	public function testAttributeNamesAreLowercased()
	{
		$collection = new AttributeCollection;

		$this->assertSame(
			'foobar54z',
			$collection->normalizeName('FOOBAR54Z')
		);
	}

	/**
	* @testdox add() creates instances of s9e\TextFormatter\ConfigBuilder\Items\Attribute
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