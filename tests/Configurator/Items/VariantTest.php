<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\Variant
*/
class VariantTest extends Test
{
	/**
	* @testdox get() returns the default value
	*/
	public function testGetDefault()
	{
		$variant = new Variant(42);
		$this->assertSame(42, $variant->get());
	}

	/**
	* @testdox get('Javascript') returns the 'Javascript' variant if it exists
	*/
	public function testGetVariant()
	{
		$variant = new Variant(42);
		$variant->set('Javascript', 55);
		$this->assertSame(55, $variant->get('Javascript'));
	}

	/**
	* @testdox get('Javascript') returns the default value if the 'Javascript' variant does not exist
	*/
	public function testGetInexistentVariant()
	{
		$variant = new Variant(42);
		$this->assertSame(42, $variant->get('Javascript'));
	}

	/**
	* @testdox has('Javascript') returns TRUE if it has a 'Javascript' variant
	*/
	public function testHasTrue()
	{
		$variant = new Variant(42);
		$variant->set('Javascript', 55);
		$this->assertTrue($variant->has('Javascript'));
	}

	/**
	* @testdox has('Javascript') returns FALSE if it does not have a 'Javascript' variant
	*/
	public function testHasFalse()
	{
		$variant = new Variant(42);
		$variant->set('foo', 55);
		$this->assertFalse($variant->has('Javascript'));
	}

	/**
	* @testdox Creating a new Variant with a Variant value makes it copy its default value and its variants instead
	*/
	public function testVariantVariant()
	{
		$variant = new Variant(42);
		$variant->set('Javascript', 55);

		$another = new Variant($variant);

		$this->assertNotSame($variant, $another);
		$this->assertEquals($variant, $another);
	}
}