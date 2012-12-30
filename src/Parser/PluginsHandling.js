/**
* @type {!Object.<!Object>}
*/
var plugins = {};

/**
* Disable a plugin
*
* @param {!string} pluginName Name of the plugin
*/
function disablePlugin(pluginName)
{
	if (plugins[pluginName])
	{
		plugins[pluginName].isDisabled = true;
	}
}

/**
* Enable a plugin
*
* @param {!string} pluginName Name of the plugin
*/
function enablePlugin(pluginName)
{
	if (plugins[pluginName])
	{
		plugins[pluginName].isDisabled = false;
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
		var pos   = matches['index'],
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
	for (var pluginName in plugins)
	{
		var plugin = plugins[pluginName];

		if (plugin.isDisabled)
		{
			continue;
		}

		if (plugin.quickMatch
		 && text.indexOf(plugin.quickMatch) < 0)
		{
			continue;
		}

		var matches = [];

		if (plugin.regexp)
		{
			matches = getMatches(plugin.regexp);

			var cnt = matches.length;

			if (!cnt)
			{
				continue;
			}

			if (cnt > plugin.regexpLimit)
			{
				if (plugin.regexpLimitAction === 'abort')
				{
					throw (pluginName + ' limit exceeded');
				}

				matches = matches.slice(0, plugin.regexpLimit);

				var msg = 'Regexp limit exceeded. Only the allowed number of matches will be processed',
					context = {
						'pluginName' : pluginName,
						'limit'      : plugin.regexpLimit
					};

				if (plugin.regexpLimitAction !== 'ignore')
				{
					logger.warn(msg, context);
				}
			}
		}

		// Execute the plugin's parser, which will add tags via addStartTag() and others
		plugin[pluginName].parse(text, matches);
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
	if (!plugins[pluginName])
	{
		plugins[pluginName] = {};
	}

	plugin[pluginName].parser = parser;
}