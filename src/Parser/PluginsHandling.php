<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

use InvalidArgumentException;
use RuntimeException;

trait PluginsHandling
{
	/**
	* @var array Array of callbacks, using plugin names as keys
	*/
	protected $pluginParsers = [];

	/**
	* @var array
	*/
	protected $pluginsConfig;

	/**
	* Disable a plugin
	*
	* @param  string $pluginName Name of the plugin
	* @return void
	*/
	public function disablePlugin($pluginName)
	{
		if (isset($this->pluginsConfig[$pluginName]))
		{
			// Copy the plugin's config to remove the reference
			$pluginConfig = $this->pluginsConfig[$pluginName];
			unset($this->pluginsConfig[$pluginName]);

			// Update the value and replace the plugin's config
			$pluginConfig['isDisabled'] = true;
			$this->pluginsConfig[$pluginName] = $pluginConfig;
		}
	}

	/**
	* Enable a plugin
	*
	* @param  string $pluginName Name of the plugin
	* @return void
	*/
	public function enablePlugin($pluginName)
	{
		if (isset($this->pluginsConfig[$pluginName]))
		{
			$this->pluginsConfig[$pluginName]['isDisabled'] = false;
		}
	}

	/**
	* Execute all the plugins
	*
	* @return void
	*/
	protected function executePluginParsers()
	{
		foreach ($this->pluginsConfig as $pluginName => $pluginConfig)
		{
			if (!empty($pluginConfig['isDisabled']))
			{
				continue;
			}

			if (isset($pluginConfig['quickMatch'])
			 && strpos($this->text, $pluginConfig['quickMatch']) === false)
			{
				continue;
			}

			$matches = [];

			if (isset($pluginConfig['regexp']))
			{
				$cnt = preg_match_all(
					$pluginConfig['regexp'],
					$this->text,
					$matches,
					PREG_SET_ORDER | PREG_OFFSET_CAPTURE
				);

				if (!$cnt)
				{
					continue;
				}

				if ($cnt > $pluginConfig['regexpLimit'])
				{
					if ($pluginConfig['regexpLimitAction'] === 'abort')
					{
						throw new RuntimeException($pluginName . ' limit exceeded');
					}

					$matches = array_slice($matches, 0, $pluginConfig['regexpLimit']);

					$msg = 'Regexp limit exceeded. Only the allowed number of matches will be processed';
					$context = [
						'pluginName' => $pluginName,
						'limit'      => $pluginConfig['regexpLimit']
					];

					if ($pluginConfig['regexpLimitAction'] === 'warn')
					{
						$this->logger->warn($msg, $context);
					}
				}
			}

			// Cache a new instance of this plugin's parser if there isn't one already
			if (!isset($this->pluginParsers[$pluginName]))
			{
				$className = (isset($pluginConfig['className']))
				           ? $pluginConfig['className']
				           : 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Parser';

				// Register the parser as a callback
				$this->pluginParsers[$pluginName] = [
					new $className($this, $pluginConfig),
					'parse'
				];
			}

			// Execute the plugin's parser, which will add tags via $this->addStartTag() and others
			call_user_func($this->pluginParsers[$pluginName], $this->text, $matches);
		}
	}

	/**
	* Register a parser
	*
	* Can be used to add a new parser with no plugin config, or pre-generate a parser for an
	* existing plugin
	*
	* @param  string   $pluginName
	* @param  callback $parser
	* @return void
	*/
	public function registerParser($pluginName, $parser)
	{
		if (!is_callable($parser))
		{
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback');
		}

		// Create an empty config for this plugin to ensure it is executed
		if (!isset($this->pluginsConfig[$pluginName]))
		{
			$this->pluginsConfig[$pluginName] = [];
		}

		$this->pluginParsers[$pluginName] = $parser;
	}
}