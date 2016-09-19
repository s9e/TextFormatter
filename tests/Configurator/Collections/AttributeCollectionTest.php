<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\AttributeCollection;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\AttributeCollection
*/
class AttributeCollectionTest extends Test
{
	/**
	* @testdox add() returns an instance of s9e\TextFormatter\Configurator\Items\Attribute
	*/
	public function testAddNormalizeValue()
	{
		$collection = new AttributeCollection;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Attribute',
			$collection->add('x')
		);
	}

	/**
	* @testdox add() normalizes the attribute name
	*/
	public function testAddNormalizeKey()
	{
		$collection = new AttributeCollection;
		$collection->add('x');

		$this->assertTrue($collection->exists('X'));
	}

	/**
	* @testdox delete() normalizes the attribute name
	*/
	public function testDeleteNormalizeKey()
	{
		$collection = new AttributeCollection;
		$collection->add('X');
		$collection->delete('x');

		$this->assertFalse($collection->exists('X'));
	}

	/**
	* @testdox exists() normalizes the attribute name
	*/
	public function testExistsNormalizeKey()
	{
		$collection = new AttributeCollection;
		$collection->add('X');

		$this->assertTrue($collection->exists('x'));
	}

	/**
	* @testdox get() normalizes the attribute name
	*/
	public function testGetNormalizeKey()
	{
		$collection = new AttributeCollection;
		$collection->add('X');

		$this->assertNotNull($collection->get('x'));
	}

	/**
	* @testdox set() normalizes the attribute name
	*/
	public function testSetNormalizeKey()
	{
		$collection = new AttributeCollection;
		$collection->set('x', new Attribute);

		$this->assertTrue($collection->exists('X'));
	}

	/**
	* @testdox Replaces duplicates by default
	*/
	public function testDuplicateDefault()
	{
		$collection = new AttributeCollection;
		$attr1 = $collection->add('x');
		$attr2 = $collection->add('x');

		$this->assertSame($attr2, $collection->get('x'));
		$this->assertNotSame($attr1, $attr2);
	}

	/**
	* @testdox Throws an meaningful exception message when creating an Attribute that already exists
	* @expectedException RuntimeException
	* @expectedExceptionMessage Attribute 'x' already exists
	*/
	public function testDuplicateError()
	{
		$collection = new AttributeCollection;
		$collection->onDuplicate('error');
		$collection->add('x');
		$collection->add('x');
	}

	/**
	* @testdox Throws an exception when accessing an Attribute that does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Attribute 'x' does not exist
	*/
	public function testNotExist()
	{
		$collection = new AttributeCollection;
		$collection->get('x');
	}
}