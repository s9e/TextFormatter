<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
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

class Attribute implements ConfigProvider
{
	public function __get($propName)
	{
		$methodName = 'get' . \ucfirst($propName);

		if (\method_exists($this, $methodName))
			return $this->$methodName();

		if (!\property_exists($this, $propName))
			throw new RuntimeException("Property '" . $propName . "' does not exist");

		return $this->$propName;
	}

	public function __set($propName, $propValue)
	{
		$methodName = 'set' . \ucfirst($propName);

		if (\method_exists($this, $methodName))
		{
			$this->$methodName($propValue);

			return;
		}

		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;

			return;
		}

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

		if (\is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . \get_class($this->$propName) . "' with instance of '" . \get_class($propValue) . "'");
		}
		else
		{
			$oldType = \gettype($this->$propName);
			$newType = \gettype($propValue);

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
				$tmp = $propValue;
				\settype($tmp, $oldType);
				\settype($tmp, $newType);

				if ($tmp !== $propValue)
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);

				\settype($propValue, $oldType);
			}
		}

		$this->$propName = $propValue;
	}

	public function __isset($propName)
	{
		$methodName = 'isset' . \ucfirst($propName);

		if (\method_exists($this, $methodName))
			return $this->$methodName();

		return isset($this->$propName);
	}

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

	protected $markedSafe = array();



	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}

	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}

	public function isSafeInJS()
	{
		return $this->isSafe('InJS');
	}

	public function markAsSafeAsURL()
	{
		$this->markedSafe['AsURL'] = \true;

		return $this;
	}

	public function markAsSafeInCSS()
	{
		$this->markedSafe['InCSS'] = \true;

		return $this;
	}

	public function markAsSafeInJS()
	{
		$this->markedSafe['InJS'] = \true;

		return $this;
	}

	public function resetSafeness()
	{
		$this->markedSafe = array();

		return $this;
	}

	protected $defaultValue;

	protected $filterChain;

	protected $generator;

	protected $required = \true;

	public function __construct(array $options = \null)
	{
		$this->filterChain = new AttributeFilterChain;

		if (isset($options))
			foreach ($options as $optionName => $optionValue)
				$this->__set($optionName, $optionValue);
	}

	protected function isSafe($context)
	{
		$methodName = 'isSafe' . $context;
		foreach ($this->filterChain as $filter)
			if ($filter->$methodName())
				return \true;

		return !empty($this->markedSafe[$context]);
	}

	public function setGenerator($callback)
	{
		if (!($callback instanceof ProgrammableCallback))
			$callback = new ProgrammableCallback($callback);

		$this->generator = $callback;
	}

	public function asConfig()
	{
		$vars = \get_object_vars($this);
		unset($vars['markedSafe']);

		return ConfigHelper::toArray($vars);
	}
}