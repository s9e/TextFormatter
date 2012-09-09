<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\TagCollection;
use s9e\TextFormatter\ConfigBuilder\Items\Tag;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\TagCollection
*/
class TagCollectionTest extends Test
{
	/**
	* @testdox add() returns an instance of s9e\TextFormatter\ConfigBuilder\Items\Tag
	*/
	public function testAddNormalizeValue()
	{
		$collection = new TagCollection;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\Items\\Tag',
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
}