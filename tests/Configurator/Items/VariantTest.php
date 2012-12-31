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

	/**
	* @testdox __construct() can be called with no arguments, and the default value will be NULL
	*/
	public function testDefaultNull()
	{
		$variant = new Variant;
		$this->assertNull($variant->get());
	}

	/**
	* @testdox setDynamic() saves a dynamic variant whose callback does not get called if the variant is not read
	*/
	public function testSetDynamicNoRead()
	{
		$callback = $this->getMock('stdClass', array('foo'));
		$callback->expects($this->never())
		         ->method('foo');

		$variant = new Variant;
		$variant->setDynamic('foo', array($callback, 'foo'));

		$variant->get();
	}

	/**
	* @testdox setDynamic() saves a dynamic variant whose callback is called and its value returned when the variant is retrieved
	*/
	public function testSetDynamicRead()
	{
		$callback = $this->getMock('stdClass', array('foo'));
		$callback->expects($this->once())
		         ->method('foo')
		         ->will($this->returnValue(42));

		$variant = new Variant;
		$variant->setDynamic('foo', array($callback, 'foo'));

		$this->assertSame(42, $variant->get('foo'));
	}

	/**
	* @testdox setDynamic() saves a dynamic variant whose callback is called and its value returned everytime the variant is retrieved
	*/
	public function testSetDynamicReads()
	{
		$callback = $this->getMock('stdClass', array('foo'));
		$callback->expects($this->exactly(2))
		         ->method('foo')
		         ->will($this->onConsecutiveCalls(42, 55));

		$variant = new Variant;
		$variant->setDynamic('foo', array($callback, 'foo'));

		$this->assertSame(42, $variant->get('foo'));
		$this->assertSame(55, $variant->get('foo'));
	}

	/**
	* @testdox setDynamic() throws an exception on invalid callback
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage valid callback
	*/
	public function testSetDynamicInvalid()
	{
		$variant = new Variant;
		$variant->setDynamic('foo', '*invalid*');
	}
}