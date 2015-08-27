<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
class ConfigOptimizer
{
	protected $configValues;
	protected $encoder;
	protected $jsLengths;
	public function __construct(Encoder $encoder)
	{
		$this->encoder = $encoder;
		$this->reset();
	}
	public function getVarDeclarations()
	{
		\asort($this->jsLengths);
		$src = '';
		foreach (\array_keys($this->jsLengths) as $varName)
		{
			$configValue = $this->configValues[$varName];
			if ($configValue->isDeduplicated())
				$src .= '/** @const */ var ' . $varName . '=' . $this->encoder->encode($configValue->getValue()) . ";\n";
		}
		return $src;
	}
	public function optimize($object)
	{
		return \current($this->optimizeObjectContent(array($object)))->getValue();
	}
	public function reset()
	{
		$this->configValues = array();
		$this->jsLengths    = array();
	}
	protected function deduplicateConfigValues()
	{
		\arsort($this->jsLengths);
		foreach (\array_keys($this->jsLengths) as $varName)
		{
			$configValue = $this->configValues[$varName];
			if ($configValue->getUseCount() > 1)
				$configValue->deduplicate();
		}
	}
	protected function getVarName($js)
	{
		return \sprintf('o%08X', \crc32($js));
	}
	protected function optimizeObjectContent($object)
	{
		$object = $this->recordObject($object);
		$this->deduplicateConfigValues();
		return $object->getValue();
	}
	protected function recordObject($object)
	{
		$js      = $this->encoder->encode($object);
		$varName = $this->getVarName($js);
		$object  = $this->recordObjectContent($object);
		if (!isset($this->configValues[$varName]))
		{
			$this->configValues[$varName]       = new ConfigValue($object, $varName);
			$this->jsLengths[$varName] = \strlen($js);
		}
		$this->configValues[$varName]->incrementUseCount();
		return $this->configValues[$varName];
	}
	protected function recordObjectContent($object)
	{
		foreach ($object as $k => $v)
			if (\is_array($v) || $v instanceof Dictionary)
				$object[$k] = $this->recordObject($v);
		return $object;
	}
}