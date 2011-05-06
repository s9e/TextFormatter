s9e = {};
s9e.Parser = function()
{
	var 
		/** @const */
		LOG_DEBUG   = 'debug',
		/** @const */
		LOG_WARNING = 'warning',
		/** @const */
		LOG_ERROR   = 'error',

		/** @const */
		START_TAG        = 1,
		/** @const */
		END_TAG          = 2,
		/** @const */
		SELF_CLOSING_TAG = 3,

		/** @const */
		TRIM_CHARLIST = " \n\r\t\0\x0B",

		/** @type {Array} */
		unprocessedTags,
		/** @type {Array} */
		processedTags,
		/** @type {Object} */
		openTags,
		/** @type {Object} */
		openStartTags,
		/** @type {Object} */
		cntOpen,
		/** @type {Object} */
		cntTotal,
		/** @type {string} */
		text,
		/** @type {Object|boolean} */
		currentTag,
		/** @type {string|boolean} */
		currentAttribute
	;

	/** @param {!string} _text */
	function reset(_text)
	{
		text = _text;

		_log = {};
		_log[LOG_DEBUG] = [];
		_log[LOG_WARNING] = [];
		_log[LOG_ERROR] = [];

		unprocessedTags = [];
		processedTags   = [];
		openTags        = [];
		openStartTags   = [];
		cntOpen         = [];
		cntTotal        = [];

		currentTag = false;
		currentAttribute = false;
	}

	/**
	* @param {!string} type
	* @param {!Object} entry
	*/
	function log(type, entry)
	{
		if (currentTag)
		{
			entry['tagName'] = currentTag.name;

			if (currentAttribute)
			{
				entry['attrName'] = currentAttribute;
			}

			if (!('pos' in entry))
			{
				entry['pos'] = currentTag.pos;
			}
		}

		_log[type].push(entry);
	}

	/** @param {!RegExp} regexp */
	function getMatches(regexp)
	{
		var ret = [],
			matches;

		while (matches = regexp.exec(text))
		{
			var pos   = regexp.lastIndex - matches[0].length,
				match = [[matches.shift(), pos]],
				str;

			while (str = matches.shift())
			{
				match.push([str, text.indexOf(str, pos)]);
				pos += str.length;
			}

			ret.push(match);
		}

		return ret;
	}

	function executePluginParsers()
	{
		for (pluginName in pluginsConfig)
		{
			var pluginConfig = pluginsConfig[pluginName],
				matches      = {};

			if ('regexp' in pluginConfig)
			{
			}
		}
	}

	return {
		parse: function(_text)
		{
			reset(_text);
			executePluginParsers();
			normalizeTags();
			sortTags();
			processTags();

			return output();
		}
	}
}();