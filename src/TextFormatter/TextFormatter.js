s9e = {};

/**
* @typedef {{
*	pos: !number,
*	len: !number,
*	name: !string,
*	type: !number
* }}
*/
var Tag;

/**
* @typedef {{
*	name: !string,
*	pluginName: !string,
*	suffix: !string,
*	context: !Object
* }}
*/
var StubTag;

s9e['TextFormatter'] = function()
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
		/** @const */
		rtrimRegExp = new RegExp('[' + TRIM_CHARLIST + ']*$'),
		/** @const */
		ltrimRegExp = new RegExp('^[' + TRIM_CHARLIST + ']*'),

		/** @type {!Object} */
		_log,

		/** @const @type {!Object} */
		tagsConfig = {/* DO NOT EDIT*/},
		/** @const @type {!Object} */
		filtersConfig = {/* DO NOT EDIT*/},
		/** @const @type {!Object} */
		pluginsConfig = {/* DO NOT EDIT*/},
		/** @const @type {!Object.<string, function(!string, !Object):Array>} */
		pluginParsers = {/* DO NOT EDIT*/},
		/** @const @type {!Object.<string, function>} */
		callbacks = {/* DO NOT EDIT*/},

		/** @type {!string} */
		text,
		/** @type {!Array.<Tag>} */
		unprocessedTags,
		/** @type {!Array.<Tag>} */
		processedTags,
		/** @type {!Object.<Number,1>} */
		processedTagIds,
		/** @type {!Array.<StubTag>} */
		openTags,
		/** @type {!Object} */
		openStartTags,
		/** @type {!Object} */
		cntOpen,
		/** @type {!Object} */
		cntTotal,
		/** @type {!Tag} */
		currentTag,
		/** @type {!string} */
		currentAttribute,
		/** @type {!Object} */
		context,
		/** @type {!number} */
		pos,

		xslt = new XSLTProcessor()
	;

	xslt['importStylesheet'](new DOMParser().parseFromString('', 'text/xml'));

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

	/** @param {!Object} obj */
	function clone(obj)
	{
		return JSON.parse(JSON.stringify(obj));
	}

	/**
	* @param {!RegExp} regexp
	* @param {!Array}  container
	*/
	function getMatches(regexp, container)
	{
		var cnt = 0,
			matches;

		// reset the regexp
		regexp.lastIndex = 0;

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

			container.push(match);
			++cnt;
		}

		return cnt;
	}

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
		processedTagIds = {};
		openTags        = [];
		openStartTags   = {};
		cntOpen         = {};
		cntTotal        = {};

		delete currentTag;
		delete currentAttribute;
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
			entry['pluginName'] = currentTag.pluginName;

			if (currentAttribute)
			{
				entry['attrName'] = currentAttribute;
			}

			if (!('pos' in entry))
			{
				entry['pos'] = currentTag.pos;
				entry['len'] = currentTag.len;
			}
		}

		_log[type].push(entry);
	}

	function filter(attrVal, attrConf, filterConf)
	{
		switch (attrConf.type)
		{
			case 'url':
				var m =/^([a-z0-9]+):\/\/\S+(?:\/.*)?$/.exec(attrVal);

				if (!m)
				{
					return false;
				}

				if (!filterConf.allowedSchemes.test(m[1]))
				{
					log('error', {
						'msg'    : "URL scheme '%s' is not allowed",
						'params' : [m[1]]
					});
					return false;
				}

				var a = document.createElement('a');
				a.href = attrVal;

				if (filterConf.disallowedHosts
				 && filterConf.disallowedHosts.test(a.host))
				{
					log('error', {
						'msg'    : "URL host '%s' is not allowed",
						'params' : [a.host]
					});
					return false;
				}

				return attrVal.replace(/'/, '%27').replace(/"/, '%22');

			case 'identifier':
			case 'id':
				return /^[a-zA-Z0-9-_]+$/.test(attrVal) ? attrVal : false;

			case 'simpletext':
				return /^[a-zA-Z0-9\-+.,_ ]+$/.test(attrVal) ? attrVal : false;

			case 'text':
				return attrVal;

			case 'email':
				/**
				* NOTE: obviously, this is not meant to match precisely the whole set of theorically
				*       valid addresses. It's only there to catch honest mistakes. The actual
				*       validation should be performed by PHP's ext/filter.
				*/
				if (!/^[\w\.\-_]+@[\w\.\-_]+$/.test(attrVal))
				{
					return false;
				}

				if (attrConf.forceUrlencode)
				{
					return attrVal
						.split('')
						.map(function(c)
						{
							return '%' + c.charCodeAt(0).toString(16);
						})
						.join('');
				}

				return attrVal;

			case 'int':
			case 'integer':
				return /^-?[1-9][0-9]*$/.test(attrVal) ? attrVal : false;

			case 'float':
				return /^-?[0-9]+(?:\.[0-9]+)?(?:e[1-9][0-9]*)?$/i.test(attrVal) ? attrVal : false;

			case 'number':
				return /^[0-9]+$/.test(attrVal) ? attrVal : false;

			case 'uint':
				return /^(?:0|[1-9][0-9]*)$/.test(attrVal) ? attrVal : false;

			case 'range':
				if (!/^(?:0|-?[1-9][0-9]*)$/.test(attrVal))
				{
					return false;
				}

				if (attrVal < attrConf.min)
				{
					log('warning', {
						'msg'    : 'Value outside of range, adjusted up to %d',
						'params' : [attrConf.min]
					});
					return attrConf.min;
				}

				if (attrVal > attrConf.max)
				{
					log('warning', {
						'msg'    : 'Value outside of range, adjusted down to %d',
						'params' : [attrConf.max]
					});
					return attrConf.max;
				}

				return attrVal;

			case 'color':
				return /^(?:#[0-9a-f]{3,6}|[a-z]+)$/i.test(attrVal) ? attrVal : false;

			case 'regexp':
				var match = attrConf.regexp.exec(attrVal);

				if (!match)
				{
					return false;
				}

				if (attrConf.replaceWith)
				{
					/**
					* Two consecutive backslashes[1] are replaced with a single backslash.
					* A dollar sign followed by digits and preceded by a backslash[2] is preserved.
					* Otherwise, the corresponding match[3] is used.
					*/
					return attrConf.replaceWith.replace(
						/(\\\\)|(\\)?\$([0-9]+)/g,
						function (str, p1, p2, p3)
						{
							return (p1) ? '\\' : ((p2) ? '$' + p3 : match[p3]);
						}
					);
				}

				return attrVal;
		}

		log('debug', {
			'msg'    : "Unknown filter '%s'",
			'params' : [attrConf.type]
		});

		return false;
	}

	function output()
	{
		return asDOM();
	}

	function asDOM()
	{
		var stack = [],
			pos   = 0,
			i     = -1,
			cnt   = processedTags.length,
			DOM   = document.implementation.createDocument('', (cnt) ? 'rt' : 'pt', null),
			el    = DOM.documentElement;

		function writeElement(tagName, content)
		{
			el.appendChild(DOM.createElement(tagName)).textContent = content;
		}

		function appendText(content)
		{
			el.appendChild(DOM.createTextNode(content));
		}

		while (++i < cnt)
		{
			var tag = processedTags[i];

			/**
			* Append the text that's between last tag and this one
			*/
			if (tag.pos > pos)
			{
				appendText(text.substr(pos, tag.pos - pos));
			}

			/**
			* Capture the part of the text that belongs to this tag then move the cursor past
			* current tag
			*/
			var tagText = text.substr(tag.pos, tag.len);
			pos = tag.pos + tag.len;

			var wsBefore = '',
				wsAfter  = '';

			if (tag.trimBefore)
			{
				wsBefore = tagText.substr(0, tag.trimBefore);
				tagText  = tagText.substr(tag.trimBefore);
			}

			if (tag.trimAfter)
			{
				wsAfter = tagText.substr(-tag.trimAfter);
				tagText = tagText.substr(0, tagText.length - tag.trimAfter);
			}

			if (wsBefore !== '')
			{
				writeElement('i', wsBefore);
			}

			if (tag.type & START_TAG)
			{
				stack.push(el);
				el = el.appendChild(DOM.createElement(tag.name));

				for (var attrName in tag.attrs)
				{
					el.setAttribute(attrName, tag.attrs[attrName]);
				}

				if (tag.type & END_TAG)
				{
					el.textContent = tagText;
					el = stack.pop();
				}
				else if (tagText > '')
				{
					writeElement('st', tagText);
				}
			}
			else
			{
				if (tagText > '')
				{
					writeElement('et', tagText);
				}
				el = stack.pop();
			}

			if (wsAfter !== '')
			{
				writeElement('i', wsAfter);
			}
		}

		/**
		* Append the rest of the text, past the last tag
		*/
		if (pos < text.length)
		{
			appendText(text.substr(pos));
		}

		return DOM;
	}

	/** @param {!Tag} tag */
	function appendTag(tag)
	{
		addTrimmingInfoToTag(tag);

		processedTags.push(tag);
		processedTagIds[tag.id] = 1;

		pos = tag.pos + tag.len;

		/**
		* Maintain counters
		*/
		var tagId = getTagId(tag);

		if (tag.type & START_TAG)
		{
			++cntTotal[tag.name];

			if (tag.type === START_TAG)
			{
				++cntOpen[tag.name];

				if (openStartTags[tagId])
				{
					++openStartTags[tagId];
				}
				else
				{
					openStartTags[tagId] = 1;
				}
			}
		}
		else if (tag.type & END_TAG)
		{
			--cntOpen[tag.name];
			--openStartTags[tagId];
		}
	}

	/** @param {!Tag} tag */
	function addTrimmingInfoToTag(tag)
	{
		var tagConfig = tagsConfig[tag.name];

		/**
		* Original: "  [b]  -text-  [/b]  "
		* Matches:  "XX[b]  -text-XX[/b]  "
		*/
		if ((tag.type  &  START_TAG && tagConfig.trimBefore)
		 || (tag.type === END_TAG   && tagConfig.rtrimContent))
		{
			tag.trimBefore  = rtrimRegExp.exec(text.substr(pos, tag.pos - pos))[0].length;
			tag.len        += tag.trimBefore;
			tag.pos        -= tag.trimBefore;
		}

		/**
		* Original: "  [b]  -text-  [/b]  "
		* Matches:  "  [b]XX-text-  [/b]XX"
		*/
		if ((tag.type === START_TAG && tagConfig.ltrimContent)
		 || (tag.type  &  END_TAG   && tagConfig.trimAfter))
		{
			tag.trimAfter  = ltrimRegExp.exec(text.substr(tag.pos + tag.len))[0].length;
			tag.len       += tag.trimAfter;
		}
	}

	function executePluginRegexp(pluginName)
	{
		var pluginConfig = pluginsConfig[pluginName];

		/**
		* Some plugins have several regexps in an array, others have a single regexp as a
		* string. We convert the latter to an array so that we can iterate over it.
		*/
		var isArray = !(pluginConfig.regexp instanceof RegExp);

		var regexps = (isArray) ? pluginConfig.regexp : { 'r': pluginConfig.regexp };

		var skip = false,
			matches = {},
			cnt = 0;

		foreach(regexps, function(regexp, k)
		{
			matches[k] = [];

			if (skip)
			{
				return;
			}

			var _cnt = getMatches(
				regexp,
				matches[k]
			);

			if (!_cnt)
			{
				return;
			}

			cnt += _cnt;

			if (cnt > pluginConfig.regexpLimit)
			{
				if (pluginConfig.regexpLimitAction === 'abort')
				{
					throw pluginName + ' limit exceeded';
				}
				else
				{
					var limit = pluginConfig.regexpLimit + _cnt - cnt,
						msg   = {
							'msg' : '%1$s limit exceeded. Only the first %2$s matches will be processed',
							'params' : [pluginName, pluginConfig.regexpLimit]
						};

					matches[k] = matches[k].slice(0, limit);

					if (pluginConfig.regexpLimitAction === 'ignore')
					{
						log('debug', msg);
					}
					else
					{
						log('warning', msg);
					}

					skip = true;
				}
			}
		});

		if (!cnt)
		{
			return false;
		}

		if (!isArray)
		{
			matches = matches['r'];
		}

		return matches;
	}

	function executePluginParsers()
	{
		var tagId = 0;

		foreach(pluginsConfig, function(pluginConfig, pluginName)
		{
			if (pluginConfig.__disabled)
			{
				return;
			}

			var matches;

			if (pluginConfig.regexp)
			{
				matches = executePluginRegexp(pluginName);

				if (matches === false)
				{
					return;
				}
			}

			var tags = pluginParsers[pluginName](text, matches);

			tags.forEach(
				/**
				* @param {!Tag}    tag
				*/
				function(tag)
				{
					tag.id = ++tagId;
					tag.pluginName = pluginName;
				}
			);

			tags.forEach(
				/**
				* @param {!Tag}    tag
				*/
				function(tag)
				{
					if (tag.requires)
					{
						tag.requires.forEach(function(k, i)
						{
							tag.requires[i] = tags[k].id;
						});
					}

					unprocessedTags.push(tag);
				}
			);
		});
	}

	function normalizeTags()
	{
		var k = 0;

		unprocessedTags.forEach(
			/**
			* @param {!Tag} tag
			*/
			function(tag)
			{
				tag.name = tag.name.toUpperCase();

				if (!tagsConfig[tag.name])
				{
					log('debug', {
						'pos'    : tag.pos,
						'len'    : tag.len,
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
			}
		);
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
		cntOpen = {}

		for (var tagName in tagsConfig)
		{
			context.allowedTags[tagName] = tagName;
			cntTotal[tagName] = 0;
			cntOpen[tagName] = 0;
		}

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
			context    : clone(context)
		});

		for (var k in context.allowedTags)
		{
			if (!tagConfig.allow[k])
			{
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
	* @param  {!StubTag}    tag
	* @param  {!number} _pos
	* @return {Tag}
	*/
	function createEndTag(tag, _pos)
	{
		return {
			id     : -1,
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
				parentTagName = parentTag.name,
				parentMatches = tagConfig.rules.closeParent.some(
					function(tagName)
					{
						return (tagName === parentTagName);
					}
				);

			if (parentMatches)
			{
				/**
				* We have to close that parent. First we reinsert current tag...
				*/
				unprocessedTags.push(currentTag);

				/**
				* ...then we create a new end tag which we put on top of the stack
				*/
				currentTag = createEndTag(
					parentTag,
					currentTag.pos
				);

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
					ascendantTagName = ascendantTag.name,
					ascendantMatches = tagConfig.rules.closeAscendant.some(
						function(tagName)
						{
							return (tagName === ascendantTagName);
						}
					);

				if (ascendantMatches)
				{
					/**
					* We have to close this ascendant. First we reinsert current tag...
					*/
					unprocessedTags.push(currentTag);

					/**
					* ...then we create a new end tag which we put on top of the stack
					*/
					currentTag = createEndTag(
						ascendantTag,
						currentTag.pos
					);

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
			var parentTag, parentTagName, parentMatches;

			if (openTags.length)
			{
				parentTag     = openTags[openTags.length - 1];
				parentTagName = parentTag.name;
				parentMatches = tagConfig.rules.requireParent.some(
					function(tagName)
					{
						return (tagName === parentTagName);
					}
				);
			}

			if (!parentMatches)
			{
				var msg = (tagConfig.rules.requireParent.length === 1)
				        ? 'Tag %1$s requires %2$s as parent'
				        : 'Tag %1$s requires as parent any of: %2$s';

				log('error', {
					'msg'    : msg,
					'params' : [
						currentTag.name,
						tagConfig.rules.requireParent.join(', ')
					]
				})

				return true;
			}
		}

		return false;
	}

	function requireAscendant()
	{
		var tagConfig = tagsConfig[currentTag.name];

		if (tagConfig.rules
		 && tagConfig.rules.requireAscendant)
		{
			var i = 0,
				cnt = tagConfig.rules.requireAscendant.length;

			do
			{
				if (cntOpen[tagConfig.rules.requireAscendant[i]])
				{
					return false;
				}
			}
			while (++i < cnt);

			var msg = (cnt === 1)
			        ? 'Tag %1$s requires %2$s as ascendant'
			        : 'Tag %1$s requires as ascendant any of: %2$s';

			log('error', {
				'msg'    : msg,
				'params' : [
					currentTag.name,
					tagConfig.rules.requireAscendant.join(', ')
				]
			});

			return true;
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
				if (!processedTagIds[currentTag.requires[i]])
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
	* @param {!Tag} a
	* @param {!Tag} b
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
		     ? (a.id - b.id)
		     : (b.id - a.id);
	}

	function processCurrentTagAttributes()
	{
		if (!tagsConfig[currentTag.name].attrs)
		{
			/**
			* Remove all attributes if none are defined for this tag
			*/
			currentTag.attrs = {};
		}
		else
		{
			/**
			* Handle compound attributes
			*/
			splitCompoundAttributes();

			/**
			* Filter attributes
			*/
			filterAttributes();

			/**
			* Add default values
			*/
			addDefaultAttributeValuesToCurrentTag();

			/**
			* Check for missing required attributes
			*/
			if (currentTagRequiresMissingAttribute())
			{
				return true;
			}
		}

		return false
	}

	function currentTagRequiresMissingAttribute()
	{
		var tagConfig = tagsConfig[currentTag.name];

		for (var attrName in tagConfig.attrs)
		{
			if (tagConfig.attrs[attrName].isRequired
			 && currentTag.attrs[attrName] === undefined)
			{
				log('error', {
					'msg'    : "Missing attribute '%s'",
					'params' : [attrName]
				});

				return true;
			}
		}

		return false;
	}

	function addDefaultAttributeValuesToCurrentTag()
	{
		var tagConfig = tagsConfig[currentTag.name];

		for (var attrName in tagConfig.attrs)
		{
			if (currentTag.attrs[attrName] === undefined
			 && tagConfig.attrs[attrName].defaultValue !== undefined)
			{
				currentTag.attrs[attrName] = tagConfig.attrs[attrName].defaultValue;
			}
		}
	}

	function filterAttributes()
	{
		var tagConfig = tagsConfig[currentTag.name];

		/**
		* Tag-level preFilter callbacks
		*/
		applyTagPreFilterCallbacks();

		/**
		* Remove undefined attributes
		*/
		removeUndefinedAttributesFromCurrentTag();

		/**
		* Filter each attribute
		*/
		foreach(currentTag.attrs, function(originalVal, attrName)
		{
			currentAttribute = attrName;

			// execute preFilter callbacks
			applyAttributePreFilterCallbacks();

			// do filter/validate current attribute
			filterCurrentAttribute();

			// if the value is invalid, log the occurence, remove the attribute then skip to the
			// next attribute
			if (currentTag.attrs[attrName] === false)
			{
				log('error', {
					'msg'    : "Invalid attribute '%s'",
					'params' : [attrName]
				});

				delete currentTag.attrs[attrName];

				return;
			}

			// execute postFilter callbacks
			applyAttributePostFilterCallbacks();

			if (originalVal !== currentTag.attrs[attrName])
			{
				log('debug', {
					'msg'    : 'Attribute value was altered by the filter '
					         + '(attrName: %1$s, originalVal: %2$s, attrVal: %3$s)',
					'params' : [
						attrName,
						JSON.stringify(originalVal),
						JSON.stringify(currentTag.attrs[attrName])
					]
				});
			}
		});
		delete currentAttribute;

		/**
		* Tag-level postFilter callbacks
		*/
		applyTagPostFilterCallbacks();
	}

	function removeUndefinedAttributesFromCurrentTag()
	{
		for (var attrName in currentTag.attrs)
		{
			if (!tagsConfig[currentTag.name].attrs
			 || !tagsConfig[currentTag.name].attrs[attrName])
			{
				delete currentTag.attrs[attrName];
			}
		}
	}

	function filterCurrentAttribute()
	{
		var tagConfig  = tagsConfig[currentTag.name],
			attrConfig = tagConfig.attrs[currentAttribute];

		// no custom filters, we can hardcode the call to filter()
		currentTag.attrs[currentAttribute] = filter(
			currentTag.attrs[currentAttribute],
			attrConfig,
			filtersConfig[attrConfig.type]
		);
	}

	function applyTagPreFilterCallbacks()
	{
		if (tagsConfig[currentTag.name].preFilter)
		{
			tagsConfig[currentTag.name].preFilter.forEach(function(callbackConf)
			{
				currentTag.attrs = applyCallback(
					callbackConf,
					{ attrs: currentTag.attrs }
				);
			});
		}
	}

	function applyTagPostFilterCallbacks()
	{
		if (tagsConfig[currentTag.name].postFilter)
		{
			tagsConfig[currentTag.name].postFilter.forEach(function(callbackConf)
			{
				currentTag.attrs = applyCallback(
					callbackConf,
					{ attrs: currentTag.attrs }
				);
			});
		}
	}

	function applyAttributePreFilterCallbacks()
	{
		var attrConf = tagsConfig[currentTag.name].attrs[currentAttribute];

		if (attrConf.preFilter)
		{
			attrConf.preFilter.forEach(function(callbackConf)
			{
				currentTag.attrs[currentAttribute] = applyCallback(
					callbackConf,
					{ attrVal: currentTag.attrs[currentAttribute] }
				);
			});
		}
	}

	function applyAttributePostFilterCallbacks()
	{
		var attrConf = tagsConfig[currentTag.name].attrs[currentAttribute];

		if (attrConf.postFilter)
		{
			attrConf.postFilter.forEach(function(callbackConf)
			{
				currentTag.attrs[currentAttribute] = applyCallback(
					callbackConf,
					{ attrVal: currentTag.attrs[currentAttribute] }
				);
			});
		}
	}

	function applyCallback(conf, values)
	{
		var params = [];

		if (conf.params)
		{
			/**
			* Replace the dynamic parameters with their current value
			*/
			values['tagsConfig'] = tagsConfig;
			values['filtersConfig'] = filtersConfig;

			if (currentTag)
			{
				values['currentTag'] = currentTag;

				if (currentAttribute)
				{
					values['currentAttribute'] = currentAttribute;
				}
			}

			for (var k in conf.params)
			{
				params.push(values[k] || conf.params[k]);
			}
		}

		return callbacks[conf.callback].apply(this, params);
	}

	/*
	* As with its PHP counterpart, the behaviour of multiple compound attributes trying to set the same
	* attributes is undefined
	*/
	function splitCompoundAttributes()
	{
		var tagConfig = tagsConfig[currentTag.name];

		foreach(tagsConfig[currentTag.name].attrs, function(attrConfig, attrName)
		{
			if (attrConfig.type !== 'compound')
			{
				return;
			}

			if (attrConfig.regexpMap)
			{
				var m = attrConfig.regexp.exec(currentTag.attrs[attrName]);

				foreach(attrConfig.regexpMap, function(v, k)
				{
					if (!(k in currentTag.attrs)
					 && m[v] !== undefined)
					{
						currentTag.attrs[k] = m[v];
					}
				});
			}

			/**
			* Compound attributes are removed
			*/
			delete currentTag.attrs[attrName];
		});
	}

	/** @param {!Tag} tag */
	function getTagId(tag)
	{
		return tag.name + tag.suffix + '-' + tag.pluginName;
	}

	return {
		'parse': function(_text)
		{
			reset(_text);
			executePluginParsers();
			normalizeTags();
			sortTags();
			processTags();

			return output();
		},

		/** @param {Document} DOM */
		'render': function(DOM)
		{
			return xslt['transformToFragment'](DOM, document);
		},

		'getLog': function()
		{
			return _log;
		},

		'disablePlugin': function(pluginName)
		{
			pluginsConfig[pluginName].__disabled = 1;
		},

		'enablePlugin': function(pluginName)
		{
			pluginsConfig[pluginName].__disabled = 0;
		}
	}
}();