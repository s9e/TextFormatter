/**
* @var array Array of callbacks, using plugin names as keys
*/
var $pluginParsers = array();

/**
* @var array
*/
$pluginsConfig;

/**
* Disable a plugin
*
* @param  {!string} pluginName Name of the plugin
*/
function disablePlugin(pluginName)
{
	if (pluginsConfig[pluginName])
	{
		pluginsConfig[pluginName].isDisabled = true;
	}
}

/**
* Enable a plugin
*
* @param  {!string} pluginName Name of the plugin
*/
function enablePlugin(pluginName)
{
	if (pluginsConfig[pluginName]))
	{
		pluginsConfig[pluginName].isDisabled = false;
	}
}

/**
* Execute all the plugins
*/
function executePluginParsers()
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

				if ($pluginConfig['regexpLimitAction'] !== 'ignore')
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
			$this->pluginParsers[$pluginName] = array(
				new $className($this, $pluginConfig),
				'parse'
			);
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
function registerParser($pluginName, $parser)
{
	if (!is_callable($parser))
	{
		throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback');
	}

	// Create an empty config for this plugin to ensure it is executed
	if (!isset($this->pluginsConfig[$pluginName]))
	{
		$this->pluginsConfig[$pluginName] = array();
	}

	$this->pluginParsers[$pluginName] = $parser;
}