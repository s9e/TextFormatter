<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

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
			$this->pluginsConfig[$pluginName]['disabled'] = true;
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
			$this->pluginsConfig[$pluginName]['disabled'] = false;
		}
	}

	/**
	* Execute all the plugins and @todo
	*
	* @return void
	*/
	protected function executePluginParsers()
	{
		foreach ($this->pluginsConfig as $pluginName => $pluginConfig)
		{
			if (!empty($pluginConfig['disabled']))
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
				$matches = $this->executePluginRegexp($pluginName);

				if (empty($matches))
				{
					continue;
				}
			}

			$this->getPluginParser($pluginName)->parse($this->text, $matches);
		}
	}

	/**
	* Execute a plugin's regexps and return the result
	*
	* Takes care of regexpLimit/regexpAction
	*
	* @param  string $pluginName
	* @return mixed              An array of matches, or a 2D array of matches
	*/
	protected function executePluginRegexp($pluginName)
	{
		$pluginConfig = $this->pluginsConfig[$pluginName];

		$regexpLimit = (isset($pluginConfig['regexpLimit']))
		             ? $pluginConfig['regexpLimit']
		             : 1000;

		$regexpLimitAction = (isset($pluginConfig['regexpLimitAction']))
		                   ? $pluginConfig['regexpLimitAction']
		                   : 'ignore';

		// Some plugins have several regexps in an array, others have a single regexp as a string.
		// We convert the latter to an array so that we can iterate over it.
		$isArray = is_array($pluginConfig['regexp']);
		$regexps = ($isArray) ? $pluginConfig['regexp'] : array('r' => $pluginConfig['regexp']);

		/**
		* @var integer Total number of matches
		*/
		$cnt = 0;

		/**
		* @var array Matches returned
		*/
		$matches = array();

		foreach ($regexps as $k => $regexp)
		{
			$_cnt = preg_match_all(
				$regexp,
				$this->text,
				$matches[$k],
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			$cnt += $_cnt;

			if ($cnt > $regexpLimit)
			{
				if ($regexpLimitAction === 'abort')
				{
					throw new RuntimeException($pluginName . ' limit exceeded');
				}
				else
				{
					$limit       = $regexpLimit + $_cnt - $cnt;
					$matches[$k] = array_slice($matches[$k], 0, $limit);

					$msg = '%1$s limit exceeded. Only the first %2$s matches will be processed';
					if ($regexpLimitAction === 'ignore')
					{
						$this->logger->debug($msg, array($pluginName, $limit));
					}
					else
					{
						$this->logger->warn($msg, array($pluginName, $limit));
					}
				}

				break;
			}
		}

		return ($isArray) ? $matches : $matches['r'];
	}

	/**
	* Return a cached instance of a PluginParser
	*
	* @param  string $pluginName
	* @return PluginParser
	*/
	protected function getPluginParser($pluginName)
	{
		// Cache a new instance if there isn't one already
		if (!isset($this->pluginParsers[$pluginName]))
		{
			$pluginConfig = $this->pluginsConfig[$pluginName];

			$className = (isset($pluginConfig['className']))
			           ? $pluginConfig['className']
			           : 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Parser';

			$this->pluginParsers[$pluginName] = new $className($this, $pluginConfig);
		}

		return $this->pluginParsers[$pluginName];
	}
}