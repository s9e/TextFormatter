<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use Exception,
    s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\Collections\AttributeParserCollection;

include_once __DIR__ . '/../../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\AttributeParserCollection
*/
class AttributeParserCollectionTest extends Test
{
	/**
	* @dataProvider getAttributeParserNamesInitials
	*/
	public function testAttributeParserNamesMustStartWithALetterOrAnUnderscore($attrName, $expectedValue)
	{
		$collection = new AttributeParserCollection;

		$this->assertSame(
			$expectedValue,
			$collection->isValidName($attrName)
		);
	}

	public function getAttributeParserNamesInitials()
	{
		return array(
			array('aaa',  true),
			array('_foo', true),
			array('5aa',  false),
			array('XXX',  true)
		);
	}

	public function testAttributeParserNamesAreLowercased()
	{
		$collection = new AttributeParserCollection;

		$this->assertSame(
			'foobar54z',
			$collection->normalizeName('FOOBAR54Z')
		);
	}

	/**
	* @testdox add() creates instances of s9e\TextFormatter\ConfigBuilder\Items\AttributeParser
	*/
	public function testClassName()
	{
		$collection = new AttributeParserCollection;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\AttributeParser',
			$collection->add('x', '#x#')
		);
	}
}