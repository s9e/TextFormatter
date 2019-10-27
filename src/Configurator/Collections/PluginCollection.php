<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class PluginCollection extends NormalizedCollection
{
	protected $configurator;
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}
	public function finalize()
	{
		foreach ($this->items as $plugin)
			$plugin->finalize();
	}
	public function normalizeKey($pluginName)
	{
		if (!\preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $pluginName))
			throw new InvalidArgumentException("Invalid plugin name '" . $pluginName . "'");
		return $pluginName;
	}
	public function normalizeValue($value)
	{
		if (\is_string($value) && \class_exists($value))
			$value = new $value($this->configurator);
		if ($value instanceof ConfiguratorBase)
			return $value;
		throw new InvalidArgumentException('PluginCollection::normalizeValue() expects a class name or an object that implements s9e\\TextFormatter\\Plugins\\ConfiguratorBase');
	}
	public function load($pluginName, array $overrideProps = array())
	{
		$pluginName = $this->normalizeKey($pluginName);
		$className  = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Configurator';
		if (!\class_exists($className))
			throw new RuntimeException("Class '" . $className . "' does not exist");
		$plugin = new $className($this->configurator, $overrideProps);
		$this->set($pluginName, $plugin);
		return $plugin;
	}
	public function asConfig()
	{
		$plugins = parent::asConfig();
		foreach ($plugins as $pluginName => &$pluginConfig)
		{
			$plugin = $this->get($pluginName);
			$pluginConfig += $plugin->getBaseProperties();
			if ($pluginConfig['quickMatch'] === \false)
				unset($pluginConfig['quickMatch']);
			if (!isset($pluginConfig['regexp']))
				unset($pluginConfig['regexpLimit']);
			$className = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Parser';
			if ($pluginConfig['className'] === $className)
				unset($pluginConfig['className']);
		}
		unset($pluginConfig);
		return $plugins;
	}
}