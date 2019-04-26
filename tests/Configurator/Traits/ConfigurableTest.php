<?php

namespace s9e\TextFormatter\Tests\Configurator\Traits;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Traits\Configurable;

/**
* @covers s9e\TextFormatter\Configurator\Traits\Configurable
*/
class ConfigurableTest extends Test
{
	/**
	* @testdox __get('foo') calls getFoo() if it exists
	*/
	public function testMagicGetMethod()
	{
		$dummy = new ConfigurableTestDummy;

		$this->assertSame('foobar', $dummy->foo);
	}

	/**
	* @testdox __get($k) returns the property if it exists
	*/
	public function testMagicGet()
	{
		$dummy = new ConfigurableTestDummy;

		$this->assertSame(42, $dummy->int);
	}

	/**
	* @testdox __get() throws a RuntimeException if the property does not exist
	*/
	public function testMagicGetInexistent()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Property 'inexistent' does not exist");

		$dummy = new ConfigurableTestDummy;
		$dummy->inexistent;
	}

	/**
	* @testdox __get($k) returns null if the property is null
	*/
	public function testMagicGetNull()
	{
		$dummy = new ConfigurableTestDummy;

		$this->assertNull($dummy->null);
	}

	/**
	* @testdox __set('foo', 'bar') calls setFoo('bar') if it exists
	*/
	public function testMagicSetMethod()
	{
		$dummy = new ConfigurableTestDummy;
		$dummy->foo = 'bar';

		$this->assertSame('foobar', $dummy->foo);
	}

	/**
	* @testdox __set() can create new properties
	*/
	public function testMagicSetNew()
	{
		$dummy = new ConfigurableTestDummy;
		$dummy->inexistent = 'foo';

		$this->assertSame('foo', $dummy->inexistent);
	}

	/**
	* @testdox __set() can replace an instance of Foo with another instance of Foo
	*/
	public function testMagicSetSameObject()
	{
		$dummy = new ConfigurableTestDummy;

		$foo = new Foo;
		$dummy->fooObject = $foo;

		$this->assertSame($foo, $dummy->fooObject);
	}

	/**
	* @testdox __set() can replace an instance of Foo with an instance of FooPlus, which extends Foo
	*/
	public function testMagicSetChildObject()
	{
		$dummy = new ConfigurableTestDummy;

		$foo = new FooPlus;
		$dummy->fooObject = $foo;

		$this->assertSame($foo, $dummy->fooObject);
	}

	/**
	* @testdox __set() throws an exception if an instance of Foo would be replaced by an instance of Bar
	*/
	public function testMagicSetDifferentObject()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Cannot replace property 'fooObject' of class 's9e\\TextFormatter\\Tests\\Configurator\\Traits\\Foo' with instance of 's9e\\TextFormatter\\Tests\\Configurator\\Traits\\Bar'");

		$dummy = new ConfigurableTestDummy;

		$bar = new Bar;
		$dummy->fooObject = $bar;
	}

	/**
	* @testdox __set() can replace a scalar value with a value of the same type
	*/
	public function testMagicSetSameType()
	{
		$dummy = new ConfigurableTestDummy;
		$dummy->int = 55;

		$this->assertSame(55, $dummy->int);
	}

	/**
	* @testdox __set() can replace a scalar value with another scalar value if it can be losslessly cast to the same type
	*/
	public function testMagicSetCompatibleType()
	{
		$dummy = new ConfigurableTestDummy;
		$dummy->int = '55';

		$this->assertSame(55, $dummy->int);
	}

	/**
	* @testdox __set() can replace a boolean value by changing the string "true" to boolean true
	*/
	public function testMagicSetBoolStringTrue()
	{
		$dummy = new ConfigurableTestDummy;
		$dummy->bool = 'true';

		$this->assertTrue($dummy->bool);
	}

	/**
	* @testdox __set() can replace a boolean value by changing the string "false" to boolean false
	*/
	public function testMagicSetBoolStringFalse()
	{
		$dummy = new ConfigurableTestDummy;
		$dummy->bool = 'false';

		$this->assertFalse($dummy->bool);
	}

	/**
	* @testdox __set() throws an exception if a scalar value would be overwritten by a scalar value that cannot be losslessly cast to the same type
	*/
	public function testMagicSetIncompatibleType()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Cannot replace property 'int' of type integer with value of type string");

		$dummy = new ConfigurableTestDummy;

		$dummy->int = "55!";
	}

	/**
	* @testdox __set() will clear and repopulate a NormalizedCollection rather than overwrite it
	*/
	public function testMagicSetNormalizedCollection()
	{
		$dummy = new ConfigurableTestDummy;

		$dummy->collection->set('old', 'old');

		$values = ['foo' => 'bar', 'baz' => 'quux'];
		$dummy->collection = $values;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\NormalizedCollection',
			$dummy->collection
		);

		$this->assertSame($values, iterator_to_array($dummy->collection));
	}

	/**
	* @testdox __set() throws an exception if a NormalizedCollection would be overwritten by a non-array, non-Traversable value
	*/
	public function testMagicSetNonTraversable()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Property 'collection' expects an array or a traversable object to be passed");

		$dummy = new ConfigurableTestDummy;

		$dummy->collection = 1;
	}

	/**
	* @testdox __isset('foo') calls issetFoo() if it exists
	*/
	public function testMagicIssetMethod()
	{
		$dummy = new ConfigurableTestDummy;

		$this->assertTrue(isset($dummy->propSet));
		$this->assertFalse(isset($dummy->propUnset));
	}

	/**
	* @testdox __isset($k) returns true if the property exists
	*/
	public function testMagicIsset()
	{
		$dummy = new ConfigurableTestDummy;

		$this->assertTrue(isset($dummy->int));
	}

	/**
	* @testdox __unset('foo') calls unsetFoo() if it exists
	*/
	public function testMagicUnsetMethod()
	{
		$dummy = new ConfigurableTestDummy;

		unset($dummy->unsettable);

		$this->assertObjectHasAttribute('unsettable', $dummy);
	}

	/**
	* @testdox __unset('foo') calls unsetFoo() even if the property does not exist
	*/
	public function testMagicUnsetMethodOverride()
	{
		$dummy = new ConfigurableTestDummy;

		unset($dummy->unknown);

		$this->assertObjectHasAttribute('unknownWasUnset', $dummy);
	}

	/**
	* @testdox __unset('foo') calls $this->foo->clear() if it's an instance of Collection
	*/
	public function testMagicUnsetCollection()
	{
		$dummy = new ConfigurableTestDummy;

		$dummy->collection->set('foo', 'bar');
		$this->assertSame(1, count($dummy->collection));

		unset($dummy->collection);
		$this->assertObjectHasAttribute('collection', $dummy);
		$this->assertSame(0, count($dummy->collection));
	}

	/**
	* @testdox __unset('foo') has no effect unsetFoo() does not exist and the the property is not set
	* @doesNotPerformAssertions
	*/
	public function testMagicUnsetNone()
	{
		$dummy = new ConfigurableTestDummy;

		unset($dummy->doesNotExist);
	}

	/**
	* @testdox __unset('foo') throws an exception if unsetFoo() does not exist and the property is set
	*/
	public function testMagicUnsetFail()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Property 'notUnsettable' cannot be unset");

		$dummy = new ConfigurableTestDummy;

		unset($dummy->notUnsettable);
	}
}

class Foo {}
class FooPlus extends Foo {}
class Bar {}

class ConfigurableTestDummy
{
	use Configurable;

	protected $int = 42;
	protected $bool = false;
	protected $null = null;
	protected $collection;
	protected $fooObject;
	protected $unsettable = true;
	protected $notUnsettable = true;

	public function __construct()
	{
		$this->collection = new NormalizedCollection;
		$this->fooObject  = new Foo;
	}

	protected function getFoo()
	{
		return 'foobar';
	}

	protected function setFoo($str)
	{
		$this->foo = 'foo' . $str;
	}

	protected function issetPropSet()
	{
		return true;
	}

	protected function issetPropUnset()
	{
		return false;
	}

	protected function unsetUnsettable()
	{
		unset($this->unsettable);
	}

	protected function unsetUnknown()
	{
		$this->unknownWasUnset = 1;
	}
}