<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

use RuntimeException;

trait PluginsHandling
{
	/**
	* @var array Instantiated plugin parsers
	*/
	protected $pluginParsers = array();

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
			$this->pluginsConfig[$pluginName]['isDisabled'] = true;
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
			unset($this->pluginsConfig[$pluginName]['isDisabled']);
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

			$matches = array();

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
					$context = array(
						'pluginName' => $pluginName,
						'limit'      => $pluginConfig['regexpLimit']
					);

					if ($pluginConfig['regexpLimitAction'] === 'ignore')
					{
						$this->logger->debug($msg, $context);
					}
					else
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

				$this->pluginParsers[$pluginName] = new $className($this, $pluginConfig);
			}

			// Execute the plugin's parser, which will add tags via $this->addStartTag() and others
			$this->pluginParsers[$pluginName]->parse($this->text, $matches);
		}
	}
}