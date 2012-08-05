<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use Exception,
    s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\Collections\AttributePreprocessorCollection;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\AttributePreprocessorCollection
*/
class AttributePreprocessorCollectionTest extends Test
{
	/**
	* @group functional
	* @testdox Names are lowercased
	*/
	public function testAttributePreprocessorNamesAreLowercased()
	{
		$collection = new AttributePreprocessorCollection;

		$this->assertSame(
			'foobar54z',
			$collection->normalizeName('FOOBAR54Z')
		);
	}

	/**
	* @group functional
	* @testdox add() returns an instance of s9e\TextFormatter\ConfigBuilder\Items\AttributePreprocessor
	*/
	public function testClassName()
	{
		$collection = new AttributePreprocessorCollection;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\AttributePreprocessor',
			$collection->add('x', '#x#')
		);
	}

	/**
	* @group functional
	* @testdox getConfig() returns a list of regexps for each attribute
	*/
	public function testGetConfig()
	{
		$collection = new AttributePreprocessorCollection;

		$collection->add('x', '#x1#');
		$collection->add('x', '#x2#');
		$collection->add('y', '#y1#');
		$collection->add('y', '#y2#');

		$this->assertEquals(
			array(
				'x' => array('#x1#', '#x2#'),
				'y' => array('#y1#', '#y2#')
			),
			$collection->getConfig()
		);
	}
}