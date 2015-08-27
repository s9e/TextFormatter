<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
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
	* @param  array|Dictionary $object Original object
	* @return ConfigValue              Stored ConfigValue instance
	*/
	protected function recordObject($object)
	{
		$js      = $this->encoder->encode($object);
		$varName = $this->getVarName($js);
		$object  = $this->recordObjectContent($object);

		if (!isset($this->configValues[$varName]))
		{
			$this->configValues[$varName]       = new ConfigValue($object, $varName);
			$this->jsLengths[$varName] = strlen($js);
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
			if (is_array($v) || $v instanceof Dictionary)
			{
				$object[$k] = $this->recordObject($v);
			}
		}

		return $object;
	}
}