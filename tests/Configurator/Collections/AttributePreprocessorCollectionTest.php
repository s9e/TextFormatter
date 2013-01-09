<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use Exception;
use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;

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
	* @testdox asConfig() returns a list of [attrName, regexp] arrays
	*/
	public function testGetConfig()
	{
		$collection = new AttributePreprocessorCollection;

		$collection->add('x', '#x1#');
		$collection->add('x', '#x2#');
		$collection->add('y', '#y1#');
		$collection->add('y', '#y2#');

		$config = $collection->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertEquals(
			array(
				array('x', '#x1#'),
				array('x', '#x2#'),
				array('y', '#y1#'),
				array('y', '#y2#')
			),
			$config
		);
	}

	/**
	* @testdox asConfig() has a JavaScript variant for each attribute preprocessor
	*/
	public function testGetConfigVariant()
	{
		$collection = new AttributePreprocessorCollection;

		$collection->add('x', '#x1#');
		$collection->add('x', '#x2#');
		$collection->add('y', '#y1#');
		$collection->add('y', '#y2#');

		$config = $collection->asConfig();

		foreach ($config as $entry)
		{
			$this->assertInstanceOf(
				's9e\\TextFormatter\\Configurator\\Items\\Variant',
				$entry
			);
		}
	}

	/**
	* @testdox asConfig()'s JavaScript variants contain a RegExp object instead of a regexp string, plus a map of named subpatterns
	*/
	public function testGetConfigJavaScript()
	{
		$collection = new AttributePreprocessorCollection;

		$collection->add('x', '#(?<x1>x1)#');
		$collection->add('x', '#(?<x2>x2)#');
		$collection->add('y', '#(?<y1>y1)#');
		$collection->add('y', '#(?<y2>y2)#');

		$config = $collection->asConfig();
		ConfigHelper::filterVariants($config, 'JS');

		$rx1 = new RegExp('(x1)');
		$rx1->map = array('', 'x1');
		$rx2 = new RegExp('(x2)');
		$rx2->map = array('', 'x2');
		$ry1 = new RegExp('(y1)');
		$ry1->map = array('', 'y1');
		$ry2 = new RegExp('(y2)');
		$ry2->map = array('', 'y2');

		$this->assertEquals(
			array(
				array('x', $rx1, $rx1->map),
				array('x', $rx2, $rx2->map),
				array('y', $ry1, $ry1->map),
				array('y', $ry2, $ry2->map)
			),
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
		$attributePreprocessors = array(
			array('foo', '/a/'),
			array('foo', '/b/'),
			array('bar', '/c/')
		);

		$collection = new AttributePreprocessorCollection;
		$collection->merge($attributePreprocessors);

		$config = $collection->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertEquals(
			$attributePreprocessors,
			$config
		);
	}

	/**
	* @testdox merge() accepts a 2D array of AttributePreprocessor instances
	*/
	public function testMergeArrayOfInstances()
	{
		$collection = new AttributePreprocessorCollection;
		$collection->merge(array(
			array('foo', new AttributePreprocessor('/a/')),
			array('foo', new AttributePreprocessor('/b/')),
			array('bar', new AttributePreprocessor('/c/'))
		));

		$config = $collection->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertEquals(
			array(
				array('foo', '/a/'),
				array('foo', '/b/'),
				array('bar', '/c/')
			),
			$config
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