<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\AttributeCollection;
use s9e\TextFormatter\Configurator\Items\Attribute;

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
}