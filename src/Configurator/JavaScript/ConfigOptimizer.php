<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

/**
* This class creates local variables to deduplicate complex configValues
*/
class ConfigOptimizer
{
	/**
	* @var array Associative array of ConfigValue instances
	*/
	protected $configValues;

	/**
	* @var Encoder
	*/
	protected $encoder;

	/**
	* @var array Associative array with the length of the JavaScript representation of each value
	*/
	protected $jsLengths;

	/**
	* Constructor
	*
	* @param Encoder $encoder
	*/
	public function __construct(Encoder $encoder)
	{
		$this->encoder = $encoder;
		$this->reset();
	}

	/**
	* Return the var declarations for all deduplicated config values
	*
	* @return string JavaScript code
	*/
	public function getVarDeclarations()
	{
		asort($this->jsLengths);

		$src = '';
		foreach (array_keys($this->jsLengths) as $varName)
		{
			$configValue = $this->configValues[$varName];
			if ($configValue->isDeduplicated())
			{
				$src .= '/** @const */ var ' . $varName . '=' . $this->encoder->encode($configValue->getValue()) . ";\n";
			}
		}

		return $src;
	}

	/**
	* Optimize given config object
	*
	* @param  array|Dictionary $object Original config object
	* @return array|Dictionary         Modified config object
	*/
	public function optimize($object)
	{
		return current($this->optimizeObjectContent([$object]))->getValue();
	}

	/**
	* Clear the deduplicated config values stored in this instance
	*
	* @return void
	*/
	public function reset()
	{
		$this->configValues = [];
		$this->jsLengths    = [];
	}

	/**
	* Test whether given value can be deduplicated
	*
	* @param  mixed $value
	* @return bool
	*/
	protected function canDeduplicate($value)
	{
		if (is_array($value) || $value instanceof Dictionary)
		{
			// Do not deduplicate empty arrays and dictionaries
			return (bool) count($value);
		}

		return ($value instanceof Code);
	}

	/**
	* Mark ConfigValue instances that have been used multiple times
	*
	* @return void
	*/
	protected function deduplicateConfigValues()
	{
		arsort($this->jsLengths);
		foreach (array_keys($this->jsLengths) as $varName)
		{
			$configValue = $this->configValues[$varName];
			if ($configValue->getUseCount() > 1)
			{
				$configValue->deduplicate();
			}
		}
	}

	/**
	* Return the name of the variable that will a given value
	*
	* @param  string $js JavaScript representation of the value
	* @return string
	*/
	protected function getVarName($js)
	{
		return sprintf('o%08X', crc32($js));
	}

	/**
	* Test whether given value is iterable
	*
	* @param  mixed $value
	* @return bool
	*/
	protected function isIterable($value)
	{
		return (is_array($value) || $value instanceof Dictionary);
	}

	/**
	* Optimize given object's content
	*
	* @param  array|Dictionary $object Original object
	* @return array|Dictionary         Modified object
	*/
	protected function optimizeObjectContent($object)
	{
		$object = $this->recordObject($object);
		$this->deduplicateConfigValues();

		return $object->getValue();
	}

	/**
	* Record a given config object as a ConfigValue instance
	*
	* @param  array|Code|Dictionary $object Original object
	* @return ConfigValue                   Stored ConfigValue instance
	*/
	protected function recordObject($object)
	{
		$js      = $this->encoder->encode($object);
		$varName = $this->getVarName($js);

		if ($this->isIterable($object))
		{
			$object = $this->recordObjectContent($object);
		}

		if (!isset($this->configValues[$varName]))
		{
			$this->configValues[$varName] = new ConfigValue($object, $varName);
			$this->jsLengths[$varName]    = strlen($js);
		}
		$this->configValues[$varName]->incrementUseCount();

		return $this->configValues[$varName];
	}

	/**
	* Record the content of given config object
	*
	* @param  array|Dictionary $object Original object
	* @return array|Dictionary         Modified object containing ConfigValue instances
	*/
	protected function recordObjectContent($object)
	{
		foreach ($object as $k => $v)
		{
			if ($this->canDeduplicate($v) && !$this->shouldPreserve($v))
			{
				$object[$k] = $this->recordObject($v);
			}
		}

		return $object;
	}

	/**
	* Test whether given value should be preserved and not deduplicated
	*
	* @param  mixed $value
	* @return bool
	*/
	protected function shouldPreserve($value)
	{
		// Simple variables should be kept as-is
		return ($value instanceof Code && preg_match('(^\\w+$)', $value));
	}
}