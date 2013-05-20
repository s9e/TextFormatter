<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Traits\Configurable;

class Attribute implements ConfigProvider
{
	use Configurable;

	/**
	* @var mixed Default value used for this attribute
	*/
	protected $defaultValue;

	/**
	* @var AttributeFilterChain This attribute's filter chain
	*/
	protected $filterChain;

	/**
	* @var ProgrammableCallback Generator used to generate a value for this attribute during parsing
	*/
	protected $generator;

	/**
	* @var array Contexts in which this attribute is considered safe to be used
	*/
	protected $markedSafe = [];

	/**
	* @var bool Whether this attribute is required for the tag to be valid
	*/
	protected $required = true;

	/**
	* Constructor
	*
	* @param array $options This attribute's options
	*/
	public function __construct(array $options = null)
	{
		$this->filterChain = new AttributeFilterChain;

		if (isset($options))
		{
			foreach ($options as $optionName => $optionValue)
			{
				$this->__set($optionName, $optionValue);
			}
		}
	}

	/**
	* Return whether this attribute is safe to be used in given context
	*
	* @param  string $context Either 'AsURL', 'InCSS' or 'InJS'
	* @return bool
	*/
	protected function isSafe($context)
	{
		// Test whether this attribute was marked as safe in given context
		if (!empty($this->markedSafe[$context]))
		{
			return true;
		}

		// Test this attribute's filters
		$methodName = 'isSafe' . $context;
		foreach ($this->filterChain as $filter)
		{
			if ($filter->$methodName())
			{
				// If any filter makes it safe, we consider it safe
				return true;
			}
		}

		return false;
	}

	/**
	* Return whether this attribute is safe to be used as a URL
	*
	* @return bool
	*/
	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}

	/**
	* Return whether this attribute is safe to be used in CSS
	*
	* @return bool
	*/
	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}

	/**
	* Return whether this attribute is safe to be used in JavaScript
	*
	* @return bool
	*/
	public function isSafeInJS()
	{
		return $this->isSafe('InJS');
	}

	/**
	* Return whether this attribute is safe to be used as a URL
	*
	* @return bool
	*/
	public function markAsSafeAsURL()
	{
		return $this->markedSafe['AsURL'] = true;
	}

	/**
	* Return whether this attribute is safe to be used in CSS
	*
	* @return bool
	*/
	public function markAsSafeInCSS()
	{
		return $this->markedSafe['InCSS'] = true;
	}

	/**
	* Return whether this attribute is safe to be used in JavaScript
	*
	* @return bool
	*/
	public function markAsSafeInJS()
	{
		return $this->markedSafe['InJS'] = true;
	}

	/**
	* Set a generator for this attribute
	*
	* @param callable|ProgrammableCallback $callback
	*/
	public function setGenerator($callback)
	{
		if (!($callback instanceof ProgrammableCallback))
		{
			$callback = new ProgrammableCallback($callback);
		}

		$this->generator = $callback;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$vars = get_object_vars($this);
		unset($vars['markedSafe']);

		return ConfigHelper::toArray($vars);
	}
}