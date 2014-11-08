<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes\Configurator;

use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeCollection
*/
class BBCodeCollectionTest extends Test
{
	/**
	* @testdox add() returns an instance of s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode
	*/
	public function testAddNormalizeValue()
	{
		$collection = new BBCodeCollection;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\BBCodes\\Configurator\\BBCode',
			$collection->add('x')
		);
	}

	/**
	* @testdox Instances of s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode are added as-is
	*/
	public function testAddInstance()
	{
		$collection = new BBCodeCollection;
		$bbcode     = new BBCode;

		$this->assertSame(
			$bbcode,
			$collection->add('x', $bbcode)
		);
	}

	/**
	* @testdox add() normalizes the BBCode's name
	*/
	public function testAddNormalizeKey()
	{
		$collection = new BBCodeCollection;
		$collection->add('x');

		$this->assertTrue($collection->exists('X'));
	}

	/**
	* @testdox delete() normalizes the BBCode's name
	*/
	public function testDeleteNormalizeKey()
	{
		$collection = new BBCodeCollection;
		$collection->add('X');
		$collection->delete('x');

		$this->assertFalse($collection->exists('X'));
	}

	/**
	* @testdox exists() normalizes the BBCode's name
	*/
	public function testExistsNormalizeKey()
	{
		$collection = new BBCodeCollection;
		$collection->add('X');

		$this->assertTrue($collection->exists('x'));
	}

	/**
	* @testdox get() normalizes the BBCode's name
	*/
	public function testGetNormalizeKey()
	{
		$collection = new BBCodeCollection;
		$collection->add('X');

		$this->assertNotNull($collection->get('x'));
	}

	/**
	* @testdox set() normalizes the BBCode's name
	*/
	public function testSetNormalizeKey()
	{
		$collection = new BBCodeCollection;
		$collection->set('x', new BBCode);

		$this->assertTrue($collection->exists('X'));
	}

	/**
	* @testdox asConfig() removes the defaultAttribute value of BBCodes where it is the same as the BBCode's name
	*/
	public function testDefaultAttributeRemoved()
	{
		$collection = new BBCodeCollection;
		$collection->add('X', ['defaultAttribute' => 'x']);
		$collection->add('Y', ['defaultAttribute' => 'x']);

		$this->assertArrayMatches(
			[
				'X' => ['defaultAttribute' => null],
				'Y' => ['defaultAttribute' => 'x']
			],
			$collection->asConfig()
		);
	}

	/**
	* @testdox asConfig() removes the tagName value of BBCodes where it is the same as the BBCode's name
	*/
	public function testTagNameRemoved()
	{
		$collection = new BBCodeCollection;
		$collection->add('X', ['tagName' => 'X']);
		$collection->add('Y', ['tagName' => 'X']);

		$this->assertArrayMatches(
			[
				'X' => ['tagName' => null],
				'Y' => ['tagName' => 'X']
			],
			$collection->asConfig()
		);
	}
}