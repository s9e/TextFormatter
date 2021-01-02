<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use Traversable;

/**
* Provides magic __get, __set, __isset and __unset implementations
*/
trait Configurable
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
			if (!is_array($propValue) && !($propValue instanceof Traversable))
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
			if ($oldType === 'boolean' && preg_match('(^(?:fals|tru)e$)', $propValue))
			{
				$newType   = 'boolean';
				$propValue = ($propValue === 'true');
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
		}
		elseif (isset($this->$propName))
		{
			if ($this->$propName instanceof Collection)
			{
				$this->$propName->clear();
			}
			else
			{
				throw new RuntimeException("Property '" . $propName . "' cannot be unset");
			}
		}
	}
}