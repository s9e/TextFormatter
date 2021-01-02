<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;

/**
* @property mixed $defaultValue Default value used for this attribute
* @property AttributeFilterChain $filterChain This attribute's filter chain
* @property bool $required Whether this attribute is required for the tag to be valid
*/
class Attribute implements ConfigProvider
{
	use Configurable;
	use TemplateSafeness;

	/**
	* @var mixed Default value used for this attribute
	*/
	protected $defaultValue;

	/**
	* @var AttributeFilterChain This attribute's filter chain
	*/
	protected $filterChain;

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

		return !empty($this->markedSafe[$context]);
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$vars = get_object_vars($this);
		unset($vars['markedSafe']);

		return ConfigHelper::toArray($vars) + ['filterChain' => []];
	}
}