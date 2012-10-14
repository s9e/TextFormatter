<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use Exception;
use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\ConfigBuilder\Items\AttributePreprocessor;

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

	/**
	* @testdox merge() accepts a 2D array of regexps
	*/
	public function testMergeArrayRegexps()
	{
		$attributePreprocessors = array(
			'foo' => array('/a/', '/b/'),
			'bar' => array('/c/')
		);

		$attributePreprocessorCollection = new AttributePreprocessorCollection;
		$attributePreprocessorCollection->merge($attributePreprocessors);

		$this->assertEquals(
			$attributePreprocessors,
			$attributePreprocessorCollection->getConfig()
		);
	}

	/**
	* @testdox merge() accepts a 2D array of AttributePreprocessor instances
	*/
	public function testMergeArrayOfInstances()
	{
		$attributePreprocessorCollection = new AttributePreprocessorCollection;
		$attributePreprocessorCollection->merge(array(
			'foo' => array(
				new AttributePreprocessor('/a/'),
				new AttributePreprocessor('/b/')
			),
			'bar' => array(
				new AttributePreprocessor('/c/')
			)
		));

		$this->assertEquals(
			array(
				'foo' => array('/a/', '/b/'),
				'bar' => array('/c/')
			),
			$attributePreprocessorCollection->getConfig()
		);
	}

	/**
	* @testdox merge() accepts an instance of AttributePreprocessorCollection to copy its content
	*/
	public function testMergeInstance()
	{
		$attributePreprocessors = array(
			'foo' => array('/a/', '/b/'),
			'bar' => array('/c/')
		);

		$collection1 = new AttributePreprocessorCollection;
		$collection2 = new AttributePreprocessorCollection;

		$collection1->add('foo', '/a/');
		$collection1->add('bar', '/b/');

		$collection2->merge($collection1);

		$this->assertEquals(
			$collection1,
			$collection2
		);
	}

	/**
	* @testdox merge() throws an exception when passed a non-array, non-AttributProcessorCollection
	* @expectedException InvalidArgumentException
	*/
	public function testMergeInvalidArgument()
	{
		$collection = new AttributePreprocessorCollection;
		$collection->merge('/foo/');
	}

	/**
	* @testdox merge() throws an exception when passed a one-dimensional array
	* @expectedException InvalidArgumentException
	*/
	public function testMergeInvalidArray()
	{
		$collection = new AttributePreprocessorCollection;
		$collection->merge(array('/foo/'));
	}
}