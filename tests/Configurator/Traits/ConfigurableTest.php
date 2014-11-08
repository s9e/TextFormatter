<?php

namespace s9e\TextFormatter\Tests\Configurator\Traits;

use InvalidArgumentException;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Tests\Test;

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
	* @expectedException RuntimeException
	* @expectedExceptionMessage Property 'inexistent' does not exist
	*/
	public function testMagicGetInexistent()
	{
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
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Cannot replace property 'fooObject' of class 's9e\TextFormatter\Tests\Configurator\Traits\Foo' with instance of 's9e\TextFormatter\Tests\Configurator\Traits\Bar'
	*/
	public function testMagicSetDifferentObject()
	{
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
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Cannot replace property 'int' of type integer with value of type string
	*/
	public function testMagicSetIncompatibleType()
	{
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

		$values = array('foo' => 'bar', 'baz' => 'quux');
		$dummy->collection = $values;

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\NormalizedCollection',
			$dummy->collection
		);

		$this->assertSame($values, iterator_to_array($dummy->collection));
	}

	/**
	* @testdox __set() throws an exception if a NormalizedCollection would be overwritten by a non-array, non-Traversable value
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Property 'collection' expects an array or a traversable object to be passed
	*/
	public function testMagicSetNonTraversable()
	{
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
	*/
	public function testMagicUnsetNone()
	{
		$dummy = new ConfigurableTestDummy;

		unset($dummy->doesNotExist);
	}

	/**
	* @testdox __unset('foo') throws an exception if unsetFoo() does not exist and the property is set
	* @expectedException RuntimeException
	* @expectedExceptionMessage Property 'notUnsettable' cannot be unset
	*/
	public function testMagicUnsetFail()
	{
		$dummy = new ConfigurableTestDummy;

		unset($dummy->notUnsettable);
	}
}

class Foo {}
class FooPlus extends Foo {}
class Bar {}

class ConfigurableTestDummy
{
	/**
	* Magic getter
	*
	* Will return $this->foo if it exists, then $this->getFoo() or will throw an exception if
	* neither exists
	*
	* @param  string $propName
	* @return mixed
	*/
	public function __get($propName)
	{
		$methodName = 'get' . ucfirst($propName);

		// Look for a getter, e.g. getDefaultTemplate()
		if (method_exists($this, $methodName))
		{
			return $this->$methodName();
		}

		if (!property_exists($this, $propName))
		{
			throw new RuntimeException("Property '" . $propName . "' does not exist");
		}

		return $this->$propName;
	}

	/**
	* Magic setter
	*
	* Will call $this->setFoo($propValue) if it exists, otherwise it will set $this->foo.
	* If $this->foo is a NormalizedCollection, we do not replace it, instead we clear() it then
	* fill it back up. It will not overwrite an object with a different incompatible object (of a
	* different, non-extending class) and it will throw an exception if the PHP type cannot match
	* without incurring data loss.
	*
	* @param  string $propName
	* @param  mixed  $propValue
	* @return void
	*/
	public function __set($propName, $propValue)
	{
		$methodName = 'set' . ucfirst($propName);

		// Look for a setter, e.g. setDefaultChildRule()
		if (method_exists($this, $methodName))
		{
			$this->$methodName($propValue);

			return;
		}

		// If the property isn't already set, we just create/set it
		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;

			return;
		}

		// If we're trying to replace a NormalizedCollection, instead we clear it then
		// iteratively set new values
		if ($this->$propName instanceof NormalizedCollection)
		{
			if (!is_array($propValue)
			 && !($propValue instanceof Traversable))
			{
				throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");
			}

			$this->$propName->clear();

			foreach ($propValue as $k => $v)
			{
				$this->$propName->set($k, $v);
			}

			return;
		}

		// If this property is an object, test whether they are compatible. Otherwise, test if PHP
		// types are compatible
		if (is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
			{
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . get_class($this->$propName) . "' with instance of '" . get_class($propValue) . "'");
			}
		}
		else
		{
			// Test whether the PHP types are compatible
			$oldType = gettype($this->$propName);
			$newType = gettype($propValue);

			// If the property is a boolean, we'll accept "true" and "false" as strings
			if ($oldType === 'boolean')
			{
				if ($propValue === 'false')
				{
					$newType   = 'boolean';
					$propValue = false;
				}
				elseif ($propValue === 'true')
				{
					$newType   = 'boolean';
					$propValue = true;
				}
			}

			if ($oldType !== $newType)
			{
				// Test whether the PHP type roundtrip is lossless
				$tmp = $propValue;
				settype($tmp, $oldType);
				settype($tmp, $newType);

				if ($tmp !== $propValue)
				{
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);
				}

				// Finally, set the new value to the correct type
				settype($propValue, $oldType);
			}
		}

		$this->$propName = $propValue;
	}

	/**
	* Test whether a property is set
	*
	* @param  string $propName
	* @return bool
	*/
	public function __isset($propName)
	{
		$methodName = 'isset' . ucfirst($propName);

		if (method_exists($this, $methodName))
		{
			return $this->$methodName();
		}

		return isset($this->$propName);
	}

	/**
	* Unset a property, if the class supports it
	*
	* @param  string $propName
	* @return void
	*/
	public function __unset($propName)
	{
		$methodName = 'unset' . ucfirst($propName);

		if (method_exists($this, $methodName))
		{
			$this->$methodName();

			return;
		}

		if (!isset($this->$propName))
		{
			return;
		}

		if ($this->$propName instanceof Collection)
		{
			$this->$propName->clear();

			return;
		}

		throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}

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