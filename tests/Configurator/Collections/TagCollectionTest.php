<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\TagCollection
*/
class TagCollectionTest extends Test
{
	/**
	* @testdox add() returns an instance of s9e\TextFormatter\Configurator\Items\Tag
	*/
	public function testAddNormalizeValue()
	{
		$collection = new TagCollection;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Tag',
			$collection->add('x')
		);
	}

	/**
	* @testdox add() normalizes the tag name
	*/
	public function testAddNormalizeKey()
	{
		$collection = new TagCollection;
		$collection->add('x');

		$this->assertTrue($collection->exists('X'));
	}

	/**
	* @testdox delete() normalizes the tag name
	*/
	public function testDeleteNormalizeKey()
	{
		$collection = new TagCollection;
		$collection->add('X');
		$collection->delete('x');

		$this->assertFalse($collection->exists('X'));
	}

	/**
	* @testdox exists() normalizes the tag name
	*/
	public function testExistsNormalizeKey()
	{
		$collection = new TagCollection;
		$collection->add('X');

		$this->assertTrue($collection->exists('x'));
	}

	/**
	* @testdox get() normalizes the tag name
	*/
	public function testGetNormalizeKey()
	{
		$collection = new TagCollection;
		$collection->add('X');

		$this->assertNotNull($collection->get('x'));
	}

	/**
	* @testdox set() normalizes the tag name
	*/
	public function testSetNormalizeKey()
	{
		$collection = new TagCollection;
		$collection->set('x', new Tag);

		$this->assertTrue($collection->exists('X'));
	}

	/**
	* @testdox Throws an exception when creating a Tag that already exists
	* @expectedException RuntimeException
	* @expectedExceptionMessage Tag 'X' already exists
	*/
	public function testAlreadyExist()
	{
		$collection = new TagCollection;
		$collection->add('X');
		$collection->add('X');
	}

	/**
	* @testdox Throws an exception when accessing a Tag that does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Tag 'X' does not exist
	*/
	public function testNotExist()
	{
		$collection = new TagCollection;
		$collection->get('X');
	}
}