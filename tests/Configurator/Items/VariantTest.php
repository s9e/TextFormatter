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
	* @testdox When cast as a string, returns its default value as a string
	*/
	public function testToString()
	{
		$variant = new Variant(42);
		$this->assertSame('42', (string) $variant);
	}

	/**
	* @testdox get() returns the default value
	*/
	public function testGetDefault()
	{
		$variant = new Variant(42);
		$this->assertSame(42, $variant->get());
	}

	/**
	* @testdox get('JS') returns the 'JS' variant if it exists
	*/
	public function testGetVariant()
	{
		$variant = new Variant(42);
		$variant->set('JS', 55);
		$this->assertSame(55, $variant->get('JS'));
	}

	/**
	* @testdox get('JS') returns the default value if the 'JS' variant does not exist
	*/
	public function testGetInexistentVariant()
	{
		$variant = new Variant(42);
		$this->assertSame(42, $variant->get('JS'));
	}

	/**
	* @testdox has('JS') returns TRUE if it has a 'JS' variant
	*/
	public function testHasTrue()
	{
		$variant = new Variant(42);
		$variant->set('JS', 55);
		$this->assertTrue($variant->has('JS'));
	}

	/**
	* @testdox has('JavaScript') returns FALSE if it does not have a 'JS' variant
	*/
	public function testHasFalse()
	{
		$variant = new Variant(42);
		$variant->set('foo', 55);
		$this->assertFalse($variant->has('JS'));
	}

	/**
	* @testdox Creating a new Variant with a Variant value makes it copy its default value and its variants instead
	*/
	public function testVariantVariant()
	{
		$variant = new Variant(42);
		$variant->set('JS', 55);

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
	* @testdox __construct() can take an associative array of variants as second argument
	*/
	public function testConstructorVariants()
	{
		$variant = new Variant(null, ['foo' => 'bar']);
		$this->assertSame('bar', $variant->get('foo'));
	}

	/**
	* @testdox setDynamic() saves a dynamic variant whose callback does not get called if the variant is not read
	*/
	public function testSetDynamicNoRead()
	{
		$callback = $this->getMockBuilder('stdClass')
		                 ->setMethods(['foo'])
		                 ->getMock();
		$callback->expects($this->never())
		         ->method('foo');

		$variant = new Variant;
		$variant->setDynamic('foo', [$callback, 'foo']);

		$variant->get();
	}

	/**
	* @testdox setDynamic() saves a dynamic variant whose callback is called and its value returned when the variant is retrieved
	*/
	public function testSetDynamicRead()
	{
		$callback = $this->getMockBuilder('stdClass')
		                 ->setMethods(['foo'])
		                 ->getMock();
		$callback->expects($this->once())
		         ->method('foo')
		         ->will($this->returnValue(42));

		$variant = new Variant;
		$variant->setDynamic('foo', [$callback, 'foo']);

		$this->assertSame(42, $variant->get('foo'));
	}

	/**
	* @testdox setDynamic() saves a dynamic variant whose callback is called and its value returned everytime the variant is retrieved
	*/
	public function testSetDynamicReads()
	{
		$callback = $this->getMockBuilder('stdClass')
		                 ->setMethods(['foo'])
		                 ->getMock();
		$callback->expects($this->exactly(2))
		         ->method('foo')
		         ->will($this->onConsecutiveCalls(42, 55));

		$variant = new Variant;
		$variant->setDynamic('foo', [$callback, 'foo']);

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