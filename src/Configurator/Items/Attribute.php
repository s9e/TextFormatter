<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;

/*
* @property mixed $defaultValue Default value used for this attribute
* @property AttributeFilterChain $filterChain This attribute's filter chain
* @property ProgrammableCallback $generator Generator used to generate a value for this attribute during parsing
* @property bool $required Whether this attribute is required for the tag to be valid
*/
class Attribute implements ConfigProvider
{
	/*
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
		$methodName = 'get' . \ucfirst($propName);

		// Look for a getter, e.g. getDefaultTemplate()
		if (\method_exists($this, $methodName))
			return $this->$methodName();

		if (!\property_exists($this, $propName))
			throw new RuntimeException("Property '" . $propName . "' does not exist");

		return $this->$propName;
	}

	/*
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
		$methodName = 'set' . \ucfirst($propName);

		// Look for a setter, e.g. setDefaultChildRule()
		if (\method_exists($this, $methodName))
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
			if (!\is_array($propValue)
			 && !($propValue instanceof Traversable))
				throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");

			$this->$propName->clear();

			foreach ($propValue as $k => $v)
				$this->$propName->set($k, $v);

			return;
		}

		// If this property is an object, test whether they are compatible. Otherwise, test if PHP
		// types are compatible
		if (\is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . \get_class($this->$propName) . "' with instance of '" . \get_class($propValue) . "'");
		}
		else
		{
			// Test whether the PHP types are compatible
			$oldType = \gettype($this->$propName);
			$newType = \gettype($propValue);

			// If the property is a boolean, we'll accept "true" and "false" as strings
			if ($oldType === 'boolean')
				if ($propValue === 'false')
				{
					$newType   = 'boolean';
					$propValue = \false;
				}
				elseif ($propValue === 'true')
				{
					$newType   = 'boolean';
					$propValue = \true;
				}

			if ($oldType !== $newType)
			{
				// Test whether the PHP type roundtrip is lossless
				$tmp = $propValue;
				\settype($tmp, $oldType);
				\settype($tmp, $newType);

				if ($tmp !== $propValue)
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);

				// Finally, set the new value to the correct type
				\settype($propValue, $oldType);
			}
		}

		$this->$propName = $propValue;
	}

	/*
	* Test whether a property is set
	*
	* @param  string $propName
	* @return bool
	*/
	public function __isset($propName)
	{
		$methodName = 'isset' . \ucfirst($propName);

		if (\method_exists($this, $methodName))
			return $this->$methodName();

		return isset($this->$propName);
	}

	/*
	* Unset a property, if the class supports it
	*
	* @param  string $propName
	* @return void
	*/
	public function __unset($propName)
	{
		$methodName = 'unset' . \ucfirst($propName);

		if (\method_exists($this, $methodName))
		{
			$this->$methodName();

			return;
		}

		if (!isset($this->$propName))
			return;

		if ($this->$propName instanceof Collection)
		{
			$this->$propName->clear();

			return;
		}

		throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}

	/*
	* @var array Contexts in which this object is considered safe to be used
	*/
	protected $markedSafe = array();



	/*
	* Return whether this object is safe to be used as a URL
	*
	* @return bool
	*/
	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}

	/*
	* Return whether this object is safe to be used in CSS
	*
	* @return bool
	*/
	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}

	/*
	* Return whether this object is safe to be used in JavaScript
	*
	* @return bool
	*/
	public function isSafeInJS()
	{
		return $this->isSafe('InJS');
	}

	/*
	* Return whether this object is safe to be used as a URL
	*
	* @return self
	*/
	public function markAsSafeAsURL()
	{
		$this->markedSafe['AsURL'] = \true;

		return $this;
	}

	/*
	* Return whether this object is safe to be used in CSS
	*
	* @return self
	*/
	public function markAsSafeInCSS()
	{
		$this->markedSafe['InCSS'] = \true;

		return $this;
	}

	/*
	* Return whether this object is safe to be used in JavaScript
	*
	* @return self
	*/
	public function markAsSafeInJS()
	{
		$this->markedSafe['InJS'] = \true;

		return $this;
	}

	/*
	* Reset the "marked safe" statuses
	*
	* @return self
	*/
	public function resetSafeness()
	{
		$this->markedSafe = array();

		return $this;
	}

	/*
	* @var mixed Default value used for this attribute
	*/
	protected $defaultValue;

	/*
	* @var AttributeFilterChain This attribute's filter chain
	*/
	protected $filterChain;

	/*
	* @var ProgrammableCallback Generator used to generate a value for this attribute during parsing
	*/
	protected $generator;

	/*
	* @var bool Whether this attribute is required for the tag to be valid
	*/
	protected $required = \true;

	/*
	* Constructor
	*
	* @param array $options This attribute's options
	*/
	public function __construct(array $options = \null)
	{
		$this->filterChain = new AttributeFilterChain;

		if (isset($options))
			foreach ($options as $optionName => $optionValue)
				$this->__set($optionName, $optionValue);
	}

	/*
	* Return whether this attribute is safe to be used in given context
	*
	* @param  string $context Either 'AsURL', 'InCSS' or 'InJS'
	* @return bool
	*/
	protected function isSafe($context)
	{
		// Test this attribute's filters
		$methodName = 'isSafe' . $context;
		foreach ($this->filterChain as $filter)
			if ($filter->$methodName())
				// If any filter makes it safe, we consider it safe
				return \true;

		return !empty($this->markedSafe[$context]);
	}

	/*
	* Set a generator for this attribute
	*
	* @param callable|ProgrammableCallback $callback
	*/
	public function setGenerator($callback)
	{
		if (!($callback instanceof ProgrammableCallback))
			$callback = new ProgrammableCallback($callback);

		$this->generator = $callback;
	}

	/*
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$vars = \get_object_vars($this);
		unset($vars['markedSafe']);

		return ConfigHelper::toArray($vars);
	}
}