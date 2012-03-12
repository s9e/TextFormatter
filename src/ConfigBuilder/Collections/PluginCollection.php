<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use InvalidArgumentException,
    s9e\TextFormatter\ConfigBuilder,
    s9e\TextFormatter\ConfigBuilder\Plugins\Config as PluginConfig;

class PluginCollection extends Collection
{
	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	/**
	* Constructor
	*
	* @param ConfigBuilder $cb
	*/
	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;
	}

	/**
	* Load a default plugin
	*
	* @param  string       $pluginName    Name of the plugin
	* @param  array        $overrideProps Properties of the plugin will be overwritten with those
	* @return PluginConfig
	*/
	public function load($pluginName, array $overrideProps = array())
	{
		if (!preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $pluginName))
		{
			throw new InvalidArgumentException("Invalid plugin name '" . $pluginName . "'");
		}

		$className = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Config';

		if (!class_exists($className))
		{
			throw new RuntimeException("Class '" . $className . "' does not exist");
		}

		$this->items[$pluginName] = new $className($this->cb, $overrideProps);
	}

	/**
	* Load a custom plugin
	*
	* If a plugin of the same name exists, it will be overwritten.
	*
	* @param  string $pluginName    Name of the plugin
	* @param  string $pluginDir     Path to the plugin's directory
	* @param  string $namespace     Namespace of the plugin
	* @param  array  $overrideProps Properties of the plugin will be overwritten with those
	* @return PluginConfig
	*/
	public function loadCustom($pluginName, $pluginDir, $namespace, array $overrideProps = array())
	{
		$this->items[$pluginName] = new $className($this, $overrideProps);
	}
}