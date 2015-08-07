<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use Exception;
use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\Regexp;

/**
* @covers s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection
*/
class AttributePreprocessorCollectionTest extends Test
{
	/**
	* @testdox add() returns an instance of s9e\TextFormatter\Configurator\Items\AttributePreprocessor
	*/
	public function testClassName()
	{
		$collection = new AttributePreprocessorCollection;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\AttributePreprocessor',
			$collection->add('x', '#x#')
		);
	}

	/**
	* @testdox asConfig() returns a list of [attrName, Regexp instance, map] arrays
	*/
	public function testGetConfig()
	{
		$collection = new AttributePreprocessorCollection;
		$collection->add('x', '#(?<x1>x1)#');
		$collection->add('x', '#(?<x2>x2)#');
		$collection->add('y', '#(?<y1>y1)#');
		$collection->add('y', '#(?<y2>y2)#');

		$config = $collection->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertEquals(
			[
				['x', '#(?<x1>x1)#', ['', 'x1']],
				['x', '#(?<x2>x2)#', ['', 'x2']],
				['y', '#(?<y1>y1)#', ['', 'y1']],
				['y', '#(?<y2>y2)#', ['', 'y2']]
			],
			$config
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
		$attributePreprocessors = [
			['foo', '/a/'],
			['foo', '/b/'],
			['bar', '/c/']
		];

		$expected = new AttributePreprocessorCollection;
		foreach ($attributePreprocessors as list($attrName, $regexp))
		{
			$expected->add($attrName, $regexp);
		}

		$collection = new AttributePreprocessorCollection;
		$collection->merge($attributePreprocessors);

		$this->assertEquals($expected, $collection);
	}

	/**
	* @testdox merge() accepts a 2D array of AttributePreprocessor instances
	*/
	public function testMergeArrayOfInstances()
	{
		$collection = new AttributePreprocessorCollection;
		$collection->merge([
			['foo', new AttributePreprocessor('/a/')],
			['foo', new AttributePreprocessor('/b/')],
			['bar', new AttributePreprocessor('/c/')]
		]);

		$expected = new AttributePreprocessorCollection;
		$expected->add('foo', '/a/');
		$expected->add('foo', '/b/');
		$expected->add('bar', '/c/');

		$this->assertEquals($expected, $collection);
	}

	/**
	* @testdox merge() accepts an instance of AttributePreprocessorCollection to copy its content
	*/
	public function testMergeInstance()
	{
		$attributePreprocessors = [
			'foo' => ['/a/', '/b/'],
			'bar' => ['/c/']
		];

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
		$collection->merge(['/foo/']);
	}
}