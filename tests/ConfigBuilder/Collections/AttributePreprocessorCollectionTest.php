<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use Exception;
use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\AttributePreprocessorCollection;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\AttributePreprocessorCollection
*/
class AttributePreprocessorCollectionTest extends Test
{
	/**
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

	/**
	* @testdox Is iterable with foreach
	*/
	public function testIterable()
	{
		$collection = new AttributePreprocessorCollection;
		$collection->add('x', '#x1#');

		$fail = true;
		foreach ($collection as $k => $v)
		{
			$fail = false;
		}

		if ($fail)
		{
			$this->fail('Did not iterate');
		}
	}

	/**
	* @testdox Uses the name of the source attribute as key when iterating
	*/
	public function testIterableKeys()
	{
		$collection = new AttributePreprocessorCollection;

		$collection->add('x', '#x1#');
		$collection->add('x', '#x2#');
		$collection->add('y', '#y1#');
		$collection->add('y', '#y2#');

		$actual = '';
		foreach ($collection as $k => $v)
		{
			$actual .= $k;
		}

		$this->assertSame('xxyy', $actual);
	}
}