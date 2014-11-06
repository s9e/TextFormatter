<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use s9e\TextFormatter\Configurator\Collections\AttributeFilterChain;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;

class Attribute implements ConfigProvider
{
	use Configurable;
	use TemplateSafeness;

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