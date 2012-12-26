/**
* @type {!Object.<!Function>} Array of callbacks, using plugin names as keys
*/
var pluginParsers = {};

/**
* @type {!Object.<!Object>}
*/
var pluginsConfig = {};

/**
* Disable a plugin
*
* @param {!string} pluginName Name of the plugin
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
* @param {!string} pluginName Name of the plugin
*/
function enablePlugin(pluginName)
{
	if (pluginsConfig[pluginName])
	{
		pluginsConfig[pluginName].isDisabled = false;
	}
}

/**
* Get regexp matches in a manner similar to preg_match_all() with PREG_SET_ORDER | PREG_OFFSET_CAPTURE
*
* @param  {!RegExp} regexp
* @return {!Array.<!Array>}
*/
function getMatches(regexp)
{
	var container = [];

	// Reset the regexp
	regexp.lastIndex = 0;

	while (matches = regexp.exec(text))
	{
		var /** @type {!number} */ pos   = matches['index'],
			match = [[matches[0], pos]],
			i = 0;

		while (++i < matches.length)
		{
			var str = matches[i];

			// Sub-expressions that were not evaluated return undefined
			if (str === undefined)
			{
				match.push(['', -1]);
			}
			else
			{
				match.push([str, text.indexOf(str, pos)]);
				pos += str.length;
			}
		}

		container.push(match);
	}

	return container;
}

/**
* Execute all the plugins
*/
function executePluginParsers()
{
	for (var pluginName in pluginsConfig)
	{
		var pluginConfig = pluginsConfig[pluginName];

		if (pluginConfig.isDisabled)
		{
			continue;
		}

		if (pluginConfig.quickMatch
		 && text.indexOf(pluginConfig.quickMatch) < 0)
		{
			continue;
		}

		var matches = [];

		if (pluginConfig.regexp)
		{
			matches = getMatches(pluginConfig.regexp);

			var cnt = matches.length;

			if (!cnt)
			{
				continue;
			}

			if (cnt > pluginConfig.regexpLimit)
			{
				if (pluginConfig.regexpLimitAction === 'abort')
				{
					throw (pluginName + ' limit exceeded');
				}

				matches = matches.slice(0, pluginConfig.regexpLimit);

				var msg = 'Regexp limit exceeded. Only the allowed number of matches will be processed',
					context = {
						'pluginName' : pluginName,
						'limit'      : pluginConfig.regexpLimit
					};

				if (pluginConfig.regexpLimitAction !== 'ignore')
				{
					logger.warn(msg, context);
				}
			}
		}

		// Execute the plugin's parser, which will add tags via addStartTag() and others
		pluginParsers[pluginName](text, matches);
	}
}

/**
* Register a parser
*
* Can be used to add a new parser with no plugin config, or pre-generate a parser for an
* existing plugin
*
* @param  {!string}   pluginName
* @param  {!Function} parser
*/
function registerParser(pluginName, parser)
{
	// Create an empty config for this plugin to ensure it is executed
	if (!pluginsConfig[pluginName])
	{
		pluginsConfig[pluginName] = {};
	}

	pluginParsers[pluginName] = parser;
}