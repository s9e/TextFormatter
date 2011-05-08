s9e = {};

/**
* @typedef {{
*	pos: !number,
*	len: !number,
*	name: !string,
*   type: !number
* }}
*/
var Tag;

/**
* @typedef {{
*	pos: !number,
*	len: !number,
*	name: !string,
*   type: !number,
*	requires: Array.<number>,
*	attrs: Object
* }}
*/
var NormalizedTag;

s9e['Parser'] = function()
{
	var
		/** @const */
		START_TAG        = 1,
		/** @const */
		END_TAG          = 2,
		/** @const */
		SELF_CLOSING_TAG = 3,

		/** @const */
		TRIM_CHARLIST = " \n\r\t\0\x0B",

		/** @type {!Object} */
		tagsConfig = {/* DO NOT EDIT*/},
		/** @type {!Object} */
		pluginsConfig = {/* DO NOT EDIT*/},
		/** @type {!Object} */
		filtersConfig = {/* DO NOT EDIT*/},

		/** @type {!Object.<string, function(!string, !Object)>} */
		pluginParsers = {/* DO NOT EDIT*/},

		/** @type {string} */
		text,
		/** @type {Array.<Tag>} */
		unprocessedTags,
		/** @type {Array.<Tag>} */
		processedTags,
		/** @type {Object} */
		openTags,
		/** @type {Object} */
		openStartTags,
		/** @type {Object} */
		cntOpen,
		/** @type {Object} */
		cntTotal,
		/** @type {?Tag} */
		currentTag,
		/** @type {?string} */
		currentAttribute,
		/** @type {Object} */
		context,
		/** @type {Object} */
		_log
	;

	/** @param {!string} _text */
	function reset(_text)
	{
		text = _text;

		_log = {
			'debug': [],
			'warning': [],
			'error': []
		};

		unprocessedTags = [];
		processedTags   = [];
		openTags        = [];
		openStartTags   = [];
		cntOpen         = [];
		cntTotal        = [];

		currentTag = null;
		currentAttribute = null;
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
			var pos   = matches.index,
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

	/**
	* @param {!Object}   obj
	* @param {!Function} callback
	*/
	function foreach(obj, callback)
	{
		for (var k in obj)
		{
			callback(obj[k], k);
		}
	}

	function executePluginParsers()
	{
		foreach(pluginsConfig, function(pluginConfig, pluginName)
		{
			var matches;

			if (pluginConfig.regexp)
			{
				matches = executePluginRegexp(pluginName);

				if (matches === false)
				{
					return;
				}
			}

			foreach(
				pluginParsers[pluginName](text, matches),

				/**
				* @param {Tag}    tag
				* @param {string} k
				*/
				function(tag, k)
				{
					tag.id = pluginName + k;
					tag.pluginName = pluginName;

					if (tag.requires)
					{
						tag.requires.forEach(function(tagId, i)
						{
							tag.requires[i] = pluginName + tagId;
						});
					}

					unprocessedTags.push(tag);
				}
			);
		});
	}

	function normalizeTags()
	{
		var k = 0, normalizedTags = [];

		unprocessedTags.forEach(
			/**
			* @param {Tag} tag
			*/
			function(tag)
			{
				tag.name = tag.name.toUpperCase();

				if (!tagsConfig[tag.name])
				{
					log('debug', {
						'pos'    : tag.pos,
						'msg'    : 'Removed unknown tag %1$s from plugin %2$s',
						'params' : [tag.name, tag.pluginName]
					});

					return;
				}

				/**
				* Some methods expect those keys to always be set
				*/
				if (tag.suffix === undefined)
				{
					tag.suffix = '';
				}

				if (tag.attrs === undefined)
				{
					tag.attrs = {};
				}

				/**
				* This will serve as a tiebreaker in case two tags start at the same position
				*/
				tag._tb = k++;

				normalizedTags.push(tag);
			}
		);

		unprocessedTags = normalizedTags;
	}

	function processTags()
	{
		if (!unprocessedTags.length)
		{
			return;
		}

		context = {
			allowedTags: {}
		};
		cntTotal = {}

		for (var tagName in tagsConfig)
		{
			context.allowedTags[tagName] = tagName;
			cntTotal[tagName] = 0;
		}

		cntOpen = cntTotal;

		pos = 0;

		while (nextTag())
		{
			processTag();
		}

		/**
		* Close tags that were left open
		*/
		foreach(openTags.reverse(), function(tag)
		{
			currentTag = createEndTag(tag, text.length);
			processTag();
		});
	}

	function nextTag()
	{
		return currentTag = unprocessedTags.pop();
	}

	function processTag()
	{
		if (pos > currentTag.pos
		 || currentTagRequiresMissingTag())
		{
			log('debug', {
				'msg': 'Tag skipped'
			});
			return;
		}

		if (currentTag.type & START_TAG)
		{
			processStartTag();
		}
		else
		{
			processEndTag();
		}
	}

	function processStartTag()
	{
		//==============================================================
		// Apply closeParent and closeAscendant rules
		//==============================================================

		if (closeParent()
		 || closeAscendant())
		{
			return;
		}

		var tagName   = currentTag.name,
		    tagConfig = tagsConfig[tagName];

		if (cntOpen[tagName]  >= tagConfig.nestingLimit
		 || cntTotal[tagName] >= tagConfig.tagLimit)
		{
			return;
		}

		//==============================================================
		// Check that this tag is allowed here
		//==============================================================

		if (!context.allowedTags[tagName])
		{
			log('debug', {
				'msg'    : 'Tag %s is not allowed in this context',
				'params' : [tagName]
			});
			return;
		}

		if (requireParent()
		 || requireAscendant()
		 || processCurrentTagAttributes())
		{
			return;
		}

		//==============================================================
		// Ok, so we have a valid tag
		//==============================================================

		appendTag(currentTag);

		if (currentTag.type & END_TAG)
		{
			return;
		}

		openTags.push({
			name       : tagName,
			pluginName : currentTag.pluginName,
			suffix     : currentTag.suffix,
			context    : context
		});

		for (var k in context.allowedTags)
		{
			if (!tagConfig.allow[k])
			{
				// TODO: test this
				delete context.allowedTags[k];
			}
		}
	}

	function processEndTag()
	{
		if (!openStartTags[getTagId(currentTag)])
		{
			/**
			* This is an end tag but there's no matching start tag
			*/
			log('debug', {
				'msg'    : 'Could not find a matching start tag for tag %1$s from plugin %2$s',
				'params' : [currentTag.name, currentTag.pluginName]
			});
			return;
		}

		do
		{
			var cur = openTags.pop();
			context = cur.context;

			if (cur.name !== currentTag.name)
			{
				appendTag(createEndTag(cur, currentTag.pos));
				continue;
			}
			break;
		}
		while (1);

		appendTag(currentTag);
	}

	/**
	* @param {Tag}    tag
	* @param {number} _pos
	*/
	function createEndTag(tag, _pos)
	{
		return {
			name   : tag.name,
			pos    : _pos,
			len    : 0,
			type   : END_TAG,
			suffix : tag.suffix,
			pluginName : tag.pluginName
		};
	}

	function closeParent()
	{
		var tagConfig = tagsConfig[currentTag.name];

		if (openTags.length
		 && tagConfig.rules
		 && tagConfig.rules.closeParent)
		{
			var parentTag     = openTags[openTags.length - 1],
			    parentTagName = parentTag.name;

			if (tagConfig.rules.closeParent[parentTagName])
			{
				/**
				* We have to close that parent. First we reinsert current tag...
				*/
				unprocessedTags.push(currentTag);

				/**
				* ...then we create a new end tag which we put on top of the stack
				*/
				currentTag = {
					pos    : currentTag.pos,
					name   : parentTagName,
					pluginName : parentTag.pluginName,
					suffix : parentTag.suffix,
					len    : 0,
					type   : END_TAG
				};

				unprocessedTags.push(currentTag);

				return true;
			}
		}

		return false;
	}

	function closeAscendant()
	{
		var tagConfig = tagsConfig[currentTag.name];

		if (tagConfig.rules
		 && tagConfig.rules.closeAscendant)
		{
			var i = openTags.length;

			while (--i >= 0)
			{
				var ascendantTag     = openTags[i],
				    ascendantTagName = ascendantTag.name;

				if (tagConfig.rules.closeAscendant[ascendantTagName])
				{
					/**
					* We have to close this ascendant. First we reinsert current tag...
					*/
					unprocessedTags.push(currentTag);

					/**
					* ...then we create a new end tag which we put on top of the stack
					*/
					currentTag = {
						pos    : currentTag.pos,
						name   : ascendantTagName,
						pluginName : ascendantTag.pluginName,
						suffix : ascendantTag.suffix,
						len    : 0,
						type   : END_TAG
					};

					unprocessedTags.push(currentTag);

					return true;
				}
			}
		}

		return false;
	}

	function requireParent()
	{
		var tagConfig = tagsConfig[currentTag.name];

		if (tagConfig.rules
		 && tagConfig.rules.requireParent)
		{
			var parentTag = (openTags.length) ? openTags[openTags.length - 1] : false;

			if (!parentTag
			 || !tagConfig.rules.requireParent[parentTag.name])
			{
				var msg = (tagConfig.rules.requireParent.length === 1)
				        ? 'Tag %1$s requires %2$s as parent'
				        : 'Tag %1$s requires as parent any of: %2$s';

				var requiredParents = [],
				    tagName;

				for (tagName in tagConfig.rules.requireParent)
				{
					requiredParents.push(tagName);
				}

				log('error', {
					'msg'    : msg,
					'params' : [
						currentTag.name,
						requiredParents.join(',')
					]
				})

				return true;
			}
		}

		return false;
	}

	function requireAscendant()
	{
		var tagConfig = tagsConfig[currentTag.name],
			ascendantTagName;

		if (tagConfig.rules
		 && tagConfig.rules.requireAscendant)
		{
			for (ascendantTagName in tagConfig.rules.requireAscendant)
			{
				if (!cntOpen[ascendantTagName])
				{
					log('error', {
						'msg'    : 'Tag %1$s requires %2$s as ascendant',
						'params' : [currentTag.name, ascendantTagName]
					});

					return true;
				}
			}
		}

		return false;
	}

	function currentTagRequiresMissingTag()
	{
		if (currentTag.requires)
		{
			var i = currentTag.requires.length;

			while (--i >= 0)
			{
				var j = processedTags.length;

				while (--j >= 0)
				{
					if (processedTags[j].id
					 && processedTags[j].id === currentTag.requires[i])
					{
						break;
					}
				}

				if (j < 0)
				{
					return true;
				}
			}
		}

		return false;
	}

	function sortTags()
	{
		unprocessedTags.sort(compareTags);
	}

	/**
	* @param {Tag} a
	* @param {Tag} b
	*/
	function compareTags(a, b)
	{
		if (a.pos !== b.pos)
		{
			return b.pos - a.pos;
		}

		// This block orders zero-width tags
		if (a.len !== b.len)
		{
			if (!b.len)
			{
				return -1;
			}

			if (!a.len)
			{
				return 1;
			}
		}

		if (a.type !== b.type)
		{
			var order = {};

			order[END_TAG] = 2;
			order[SELF_CLOSING_TAG] = 1;
			order[START_TAG] = 0;

			return order[a.type] - order[b.type];
		}

		return (a.type === END_TAG)
		     ? (a._tb - b._tb)
		     : (b._tb - a._tb);
	}

	function processCurrentTagAttributes()
	{
		var tagConfig = tagsConfig[currentTag.name];

		if (!tagConfig.attrs)
		{
			/**
			* Remove all attributes if none are defined for this tag
			*/
			currentTag.attrs = {};
		}
		else
		{
			/**
			* Add default values
			*/
			for (var attrName in tagConfig.attrs)
			{
				if (tagConfig.attrs[attrName].defaultValue !== undefined
				 && currentTag.attrs[attrName] === undefined)
				{
					currentTag.attrs[attrName] = tagConfig.attrs[attrName].defaultValue;
				}
			}

			/**
			* Handle compound attributes
			*/
			splitCompoundAttributes();

			/**
			* Filter attributes
			*/
			filterAttributes();

			/**
			* Check for missing required attributes
			*/
			for (var attrName in tagConfig.attrs)
			{
				if (tagConfig.attrs[attrName].isRequired
				 && currentTag.attrs[attrName] === undefined)
				{
					currentTag.attrs[attrName] = tagConfig.attrs[attrName].defaultValue;

					log('error', {
						'msg'    : "Missing attribute '%s'",
						'params' : [attrName]
					});

					return true;
				}
			}
		}

		return false;
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