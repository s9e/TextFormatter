<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\ConfigBuilder;
use s9e\TextFormatter\ConfigBuilder\Plugins\Config as PluginConfig;

class PluginCollection extends NormalizedCollection
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
	* Validate a plugin name
	*
	* @param  string $pluginName
	* @return string
	*/
	public function normalizeKey($pluginName)
	{
		if (!preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $pluginName))
		{
			throw new InvalidArgumentException("Invalid plugin name '" . $pluginName . "'");
		}

		return $pluginName;
	}

	/**
	* Create a plugin instance/ensure it implements the correct interface
	*
	* @param  mixed Either a class name or an object that implements PluginConfig
	* @return void
	*/
	public function normalizeValue($value)
	{
		if (is_string($value) && class_exists($value))
		{
			$value = new $value($this->cb);
		}

		if ($value instanceof PluginConfig)
		{
			return $value;
		}

		throw new InvalidArgumentException('PluginCollection::normalizeValue() expects a class name or an object that implements s9e\\TextFormatter\\ConfigBuilder\\Plugins\\Config');
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
		// Validate the plugin name / class
		$pluginName = $this->normalizeKey($pluginName);
		$className  = 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Config';

		if (!class_exists($className))
		{
			throw new RuntimeException("Class '" . $className . "' does not exist");
		}

		// Create the plugin
		$plugin = new $className($this->cb, $overrideProps);

		// Save it
		$this->set($pluginName, $plugin);

		// Return it
		return $plugin;
	}
}