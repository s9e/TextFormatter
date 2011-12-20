s9e = {};

/**
* @typedef {{
*   id:  !number,
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
*	tagMate: !string,
*	context: !Object
* }}
*/
var StubTag;

s9e['TextFormatter'] = function(xsl)
{
	var
		/** @const */
		START_TAG        = 1,
		/** @const */
		END_TAG          = 2,
		/** @const */
		SELF_CLOSING_TAG = 3,

		/** @type {!Object} */
		_log,

		/** @const @type {!Object} */
		tagsConfig = {/* DO NOT EDIT */},
		/** @const @type {!Object} */
		filtersConfig = {/* DO NOT EDIT */},
		/** @const @type {!Object} */
		pluginsConfig = {/* DO NOT EDIT */},
		/** @const @type {!Object} */
		registeredNamespaces = {/* DO NOT EDIT */},
		/** @const @type {!Object.<string, function>} */
		callbacks = {/* DO NOT EDIT */},

		/** @type {!string} */
		text,
		/** @type {!number} */
		textLen,
		/** @type {!Array.<Tag>} */
		unprocessedTags,
		/** @type {!Array.<Tag>} */
		processedTags,
		/** @type {!Object.<number,number>} */
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
		/** @type {!bool} */
		hasNamespacedTags,

		/** @const */
		ENABLE_IE_WORKAROUNDS = 7,
		/** @const */
		MSXML = ENABLE_IE_WORKAROUNDS && !('XSLTProcessor' in window && 'DOMParser' in window),

		/** @const */
		ENABLE_LIVE_PREVIEW = true,

		/** @const */
		HINT_DISALLOWED_HOSTS = true,
		/** @const */
		HINT_NAMESPACES = true,
		/** @const */
		HINT_REGEXP_REPLACEWITH = true,
		/** @const */
		HINT_REOPEN_RULES = true
	;

	if (MSXML)
	{
		var xslt = new ActiveXObject('MSXML2.DOMDocument.3.0');
		xslt.async = false;
		xslt.validateOnParse = false;
		xslt.loadXML(xsl);
	}
	else
	{
		var xslt = new XSLTProcessor();
		xslt['importStylesheet'](new DOMParser().parseFromString(xsl, 'text/xml'));
	}

	if (ENABLE_IE_WORKAROUNDS && ENABLE_IE_WORKAROUNDS < 9)
	{
		if (!Array.prototype.forEach)
		{
			Array.prototype.forEach = function(fn)
			{
				var i = -1, cnt = this.length;

				while (++i < cnt)
				{
					fn(this[i], i);
				}
			}
		}

		if (!Array.prototype.some)
		{
			Array.prototype.some = function(fn)
			{
				var i = -1, cnt = this.length;

				while (++i < cnt)
				{
					if (fn(this[i], i))
					{
						return true;
					}
				}

				return false;
			}
		}
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

	/**
	* @param {!RegExp} regexp
	* @param {!Array}  container
	*/
	function getMatches(regexp, container)
	{
		// reset the regexp
		regexp.lastIndex = null;

		var cnt = 0,
			matches;

		while (matches = regexp.exec(text))
		{
			var pos   = matches.index,
				match = [[matches.shift(), pos]],
				str;

			while (matches.length)
			{
				str = matches.shift();

				if (typeof str === 'undefined')
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
			++cnt;

			if (regexp.lastIndex === 0)
			{
				regexp.lastIndex = 1;
			}
		}

		return cnt;
	}

	/**
	* @param {!Array} a1
	* @param {!Array} a2
	*/
	function contextAnd(a1, a2)
	{
		var ret = [];

		a1.forEach(function(v, k)
		{
			ret.push(v & a2[k]);
		});

		return ret;
	}

	/** @param {!string} _text */
	function reset(_text)
	{
		text    = _text;
		textLen = _text.length;

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

		hasNamespacedTags = false;

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

				if (HINT_DISALLOWED_HOSTS
				 && filterConf.disallowedHosts)
				{
					var a = document.createElement('a');
					a.href = attrVal;

					if (filterConf.disallowedHosts.test(a.hostname))
					{
						log('error', {
							'msg'    : "URL host '%s' is not allowed",
							'params' : [a.hostname]
						});
						return false;
					}
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

				if (HINT_REGEXP_REPLACEWITH && attrConf.replaceWith)
				{
					/**
					* Two consecutive backslashes[1] are replaced with a single backslash.
					* A dollar sign preceded by a backslash[2] and followed an optional curly
					* bracket followed by digits is preserved.
					* Otherwise, the corresponding match[3] is used.
					*/
					return attrConf.replaceWith.replace(
						/(\\\\)|(\\)?\$([0-9]+|\{[0-9]+\})/g,
						function (str, p1, p2, p3)
						{
							if (p3)
							{
								p3 = p3.replace(/[\{\}]/g, '');
							}

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
		function createDOM(elName)
		{
			if (MSXML)
			{
				var DOM = new ActiveXObject('MSXML2.DOMDocument.3.0');
				DOM.async = false;
				DOM.validateOnParse = false;
				DOM.loadXML('<' + elName + '/>');

				return DOM;
			}

			return document.implementation.createDocument('', elName, null);
		}

		var stack = [],
			pos   = 0,
			i     = -1,
			cnt   = processedTags.length,
			DOM   = createDOM((cnt) ? 'rt' : 'pt'),
			el    = DOM.documentElement;

		if (HINT_NAMESPACES)
		{
			/**
			* Declare all namespaces in the root node
			*/
			if (hasNamespacedTags)
			{
				var declared = {};
				processedTags.forEach(function(tag)
				{
					var pos = tag.name.indexOf(':');
					if (pos > -1)
					{
						var prefix = tag.name.substr(0, pos);

						if (!(prefix in declared))
						{
							declared[prefix] = 1;

							el.setAttributeNS(
								'http://www.w3.org/2000/xmlns/',
								'xmlns:' + prefix,
								registeredNamespaces[prefix]
							);
						}
					}
				});
			}
		}

		function createElement(tagName)
		{
			if (HINT_NAMESPACES)
			{
				var pos = tagName.indexOf(':');
				if (pos > -1)
				{
					var prefix = tagName.substr(0, pos);

					return DOM.createElementNS(
						registeredNamespaces[prefix],
						tagName
					);
				}
			}

			return DOM.createElement(tagName);
		}

		function writeElement(tagName, content)
		{
			setTextContent(
				el.appendChild(createElement(tagName)),
				content
			);
		}

		function appendText(content)
		{
			el.appendChild(DOM.createTextNode(content));
		}

		function setTextContent(el, content)
		{
			if (ENABLE_IE_WORKAROUNDS && ENABLE_IE_WORKAROUNDS < 9 && !('textContent' in el))
			{
				el.appendChild(DOM.createTextNode(content));
			}
			else
			{
				el.textContent = content;
			}
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
				wsAfter = tagText.substr(tagText.length - tag.trimAfter);
				tagText = tagText.substr(0, tagText.length - tag.trimAfter);
			}

			if (wsBefore !== '')
			{
				writeElement('i', wsBefore);
			}

			if (tag.type & START_TAG)
			{
				stack.push(el);
				el = el.appendChild(createElement(tag.name));

				for (var attrName in tag.attrs)
				{
					el.setAttribute(attrName, tag.attrs[attrName]);
				}

				if (tag.type & END_TAG)
				{
					setTextContent(el, tagText);
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
		if (pos < textLen)
		{
			appendText(text.substr(pos));
		}

		return DOM;
	}

	/** @param {!Tag} tag */
	function appendTag(tag)
	{
		processedTags.push(tag);
		processedTagIds[tag.id] = 1;

		pos = tag.pos + tag.len;

		/**
		* Maintain counters
		*/
		if (tag.type & START_TAG)
		{
			++cntTotal[tag.name];

			if (tag.type === START_TAG)
			{
				++cntOpen[tag.name];

				if (openStartTags[tag.tagMate])
				{
					++openStartTags[tag.tagMate];
				}
				else
				{
					openStartTags[tag.tagMate] = 1;
				}
			}
		}
		else if (tag.type & END_TAG)
		{
			--cntOpen[tag.name];
			--openStartTags[tag.tagMate];
		}

		/**
		* Update the context
		*/
		if (tag.type === START_TAG)
		{
			var tagConfig = tagsConfig[tag.name];

			openTags.push({
				name       : tag.name,
				pluginName : tag.pluginName,
				tagMate    : tag.tagMate,
				context    : context
			});

			var allowedChildren    = (tagConfig.isTransparent) ? context.allowedChildren : tagConfig.allowedChildren,
				allowedDescendants = contextAnd(context.allowedDescendants, tagConfig.allowedDescendants);

			context = {
				allowedChildren:    contextAnd(context.allowedDescendants, allowedChildren),
				allowedDescendants: allowedDescendants
			}
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
			tag.trimBefore  = /[ \n\r\t\0\x0B]*$/.exec(text.substr(0, tag.pos))[0].length;
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
			tag.trimAfter  = /^[ \n\r\t\0\x0B]*/.exec(text.substr(tag.pos + tag.len))[0].length;
			tag.len       += tag.trimAfter;
		}
	}

	function executePluginRegexp(pluginName, pluginConfig)
	{
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
				matches = executePluginRegexp(pluginName, pluginConfig);

				if (matches === false)
				{
					return;
				}
			}

			var tags = pluginConfig.parser(text, matches);

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

	function normalizeUnprocessedTags()
	{
		var k = 0;

		unprocessedTags.forEach(
			/**
			* @param {!Tag} tag
			*/
			function(tag)
			{
				if (tag.name.indexOf(':') === -1)
				{
					tag.name = tag.name.toUpperCase();
				}
				else
				{
					hasNamespacedTags = true;
				}

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
				if (tag.tagMate === undefined)
				{
					tag.tagMate = '';
				}

				if (tag.attrs === undefined)
				{
					tag.attrs = {};
				}

				tag.tagMate = tag.pluginName
				            + '-' + tag.name
				            + '#' + tag.tagMate;

				/**
				* Add trimming info
				*/
				addTrimmingInfoToTag(tag);
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
			allowedChildren: [],
			allowedDescendants: []
		};
		cntTotal = {}
		cntOpen = {}

		var i = -1;
		for (var tagName in tagsConfig)
		{
			if (!(++i & 7))
			{
				context.allowedChildren.push(255);
				context.allowedDescendants.push(255);
			}

			cntTotal[tagName] = 0;
			cntOpen[tagName] = 0;
		}

		pos = 0;

		while (nextTag())
		{
			processCurrentTag();
		}

		/**
		* Close tags that were left open
		*/
		while (openTags.length)
		{
			currentTag = createEndTag(
				openTags[openTags.length - 1],
				textLen
			);
			processCurrentTag();
		}
	}

	function nextTag()
	{
		return currentTag = unprocessedTags.pop();
	}

	function processCurrentTag()
	{
		if (currentTag.trimBefore
		 && pos > currentTag.pos)
		{
			var spn = pos - currentTag.pos;

			if (spn <= currentTag.trimBefore)
			{
				currentTag.pos        += spn;
				currentTag.len        -= spn;
				currentTag.trimBefore -= spn;
			}
		}

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
			processCurrentStartTag();
		}
		else
		{
			processCurrentEndTag();
		}
	}

	function processCurrentStartTag()
	{
		//==============================================================
		// Apply closeParent and closeAncestor rules
		//==============================================================

		if (closeParent()
		 || closeAncestor())
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

		if (!tagIsAllowed(tagName))
		{
			log('debug', {
				'msg'    : 'Tag %s is not allowed in this context',
				'params' : [tagName]
			});
			return;
		}

		if (requireParent()
		 || requireAncestor()
		 || processCurrentTagAttributes())
		{
			return;
		}

		//==============================================================
		// We have a valid tag, append it to the list of processed tags
		//==============================================================

		appendTag(currentTag);
	}

	function tagIsAllowed(tagName)
	{
		var n = tagsConfig[tagName].n;

		return !!(context.allowedChildren[n >> 3] & (1 << (n & 7)));
	}

	function processCurrentEndTag()
	{
		if (!openStartTags[currentTag.tagMate])
		{
			/**
			* This is an end tag but there's no matching start tag
			*/
			log('debug', {
				'msg'    : 'Could not find a matching start tag for %s',
				'params' : [currentTag.tagMate]
			});
			return;
		}

		var reopenChildren = true,
			reopenTags     = [];

		do
		{
			var lastOpenTag = openTags.pop();
			context = lastOpenTag.context;

			if (lastOpenTag.tagMate !== currentTag.tagMate)
			{
				appendTag(createEndTag(lastOpenTag, currentTag.pos));

				// Do we check for reopenChild rules?
				if (HINT_REOPEN_RULES && reopenChildren)
				{
					var tagConfig = tagsConfig[currentTag.name];

					if (tagConfig.rules
					 && tagConfig.rules.reopenChild
					 && tagConfig.rules.reopenChild.some(function(tagName)
						{
							return (tagName === lastOpenTag.name);
						})
					)
					{
						// Position the reopened tag after current tag
						var _pos = currentTag.pos + currentTag.len;

						// Test whether the tag would be out of bounds
						if (_pos < textLen)
						{
							reopenTags.push(createStartTag(lastOpenTag, _pos));
						}
					}
					else
					{
						// This tag is not meant to be reopened. Consequently, we won't reopen any
						reopenChildren = false;
					}
				}

				continue;
			}
			break;
		}
		while (1);

		appendTag(currentTag);

		if (reopenChildren)
		{
			reopenTags.forEach(function(tag)
			{
				unprocessedTags.push(tag);
			});
		}
	}

	/**
	* @param  {!StubTag} tag
	* @param  {!number}  _pos
	* @return {Tag}
	*/
	function createStartTag(tag, _pos)
	{
		return createMatchingTag(tag, _pos, START_TAG);
	}

	/**
	* @param  {!StubTag} tag
	* @param  {!number}  _pos
	* @return {Tag}
	*/
	function createEndTag(tag, _pos)
	{
		return createMatchingTag(tag, _pos, END_TAG);
	}

	/**
	* @param  {!StubTag} tag
	* @param  {!number}  _pos
	* @param  {!number}  _type
	* @return {Tag}
	*/
	function createMatchingTag(tag, _pos, _type)
	{
		var newTag = {
			id     : -1,
			name   : tag.name,
			pos    : _pos,
			len    : 0,
			type   : _type,
			tagMate    : tag.tagMate,
			pluginName : tag.pluginName
		};

		addTrimmingInfoToTag(newTag);

		return newTag;
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

	function closeAncestor()
	{
		var tagConfig = tagsConfig[currentTag.name];

		if (tagConfig.rules
		 && tagConfig.rules.closeAncestor)
		{
			var i = openTags.length;

			while (--i >= 0)
			{
				var ancestorTag     = openTags[i],
					ancestorTagName = ancestorTag.name,
					ancestorMatches = tagConfig.rules.closeAncestor.some(
						function(tagName)
						{
							return (tagName === ancestorTagName);
						}
					);

				if (ancestorMatches)
				{
					/**
					* We have to close this ancestor. First we reinsert current tag...
					*/
					unprocessedTags.push(currentTag);

					/**
					* ...then we create a new end tag which we put on top of the stack
					*/
					currentTag = createEndTag(
						ancestorTag,
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

	function requireAncestor()
	{
		var tagConfig = tagsConfig[currentTag.name];

		if (tagConfig.rules
		 && tagConfig.rules.requireAncestor)
		{
			var i = 0,
				cnt = tagConfig.rules.requireAncestor.length;

			do
			{
				if (cntOpen[tagConfig.rules.requireAncestor[i]])
				{
					return false;
				}
			}
			while (++i < cnt);

			var msg = (cnt === 1)
			        ? 'Tag %1$s requires %2$s as ancestor'
			        : 'Tag %1$s requires as ancestor any of: %2$s';

			log('error', {
				'msg'    : msg,
				'params' : [
					currentTag.name,
					tagConfig.rules.requireAncestor.join(', ')
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
		// First we order by pos descending
		if (a.pos !== b.pos)
		{
			return b.pos - a.pos;
		}

		if (!a.len || !b.len)
		{
			// Zero-width end tags are ordered after zero-width start tags so that a pair that ends
			// with a zero-width tag has the opportunity to be closed before another pair starts
			// with a zero-width tag. For example, the pairs that would enclose the letters X and Y
			// in the string "XY". Self-closing tags are ordered between end tags and start tags in
			// an attempt to keep them out of tag pairs
			if (!a.len && !b.len)
			{
				var order = {};
				order[END_TAG] = 2;
				order[SELF_CLOSING_TAG] = 1;
				order[START_TAG] = 0;

				return order[a.type] - order[b.type];
			}

			// Here, we know that only one of a or b is a zero-width tags. Zero-width tags are
			// ordered after wider tags so that they have a chance to be processed before the next
			// character is consumed, which would force them to be skipped
			return (!a.len) ? 1 : -1;
		}

		// Here we know that both tags start at the same position and have a length greater than 0.
		// We sort tags by length ascending, so that the longest matches are processed first
		if (a.len !== b.len)
		{
			return (a.len - b.len);
		}

		// Finally, if the tags start at the same position and are the same length, sort them by id
		// descending, which is our version of a stable sort (tags that were added first end up
		// being processed first)
		return b.id - a.id;
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
						originalVal,
						currentTag.attrs[attrName]
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

	return {
		/**
		* @param {!string} _text
		* @return {Document}
		*/
		'parse': function(_text)
		{
			reset(_text);
			executePluginParsers();
			normalizeUnprocessedTags();
			sortTags();
			processTags();

			return output();
		},

		/**
		* @param {!Document} DOM Intermediate representation
		* @return string
		*/
		'render': function (DOM)
		{
			if (MSXML)
			{
				return DOM.transformNode(xslt);
			}

			return xslt['transformToXML'](DOM);
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
		},

		/**
		* @param {!string} text Text to parse
		* @param {!HTMLElement} target Target element
		*/
		'preview': function(text, target)
		{
			if (!ENABLE_LIVE_PREVIEW)
			{
				throw 'Live preview is disabled';
			}

			var DOM = this.parse(text),
				document = target.ownerDocument,
				frag;

			if (MSXML)
			{
				frag = document.createDocumentFragment();

				var div  = document.createElement('div');
				div.innerHTML = DOM.transformNode(xslt);

				while (div.firstChild)
				{
					frag.appendChild(div.removeChild(div.firstChild));
				}
			}
			else
			{
				frag = xslt['transformToFragment'](DOM, document);
			}

			/**
			* Update the content of given element oldEl to match element newEl
			*
			* @param {!HTMLElement} oldEl
			* @param {!HTMLElement} newEl
			*/
			function refreshElementContent(oldEl, newEl)
			{
				var oldNodes = oldEl.childNodes,
					newNodes = newEl.childNodes,
					oldCnt = oldNodes.length,
					newCnt = newNodes.length,
					left  = 0,
					right = 0;

				/**
				* Skip the leftmost matching nodes
				*/
				while (left < oldCnt && left < newCnt)
				{
					var oldNode = oldNodes[left],
						newNode = newNodes[left];

					if (!refreshNode(oldNode, newNode))
					{
						break;
					}

					++left;
				}

				/**
				* Skip the rightmost matching nodes
				*/
				var maxRight = Math.min(oldCnt - left, newCnt - left);

				while (right < maxRight)
				{
					var oldNode = oldNodes[oldCnt - (right + 1)],
						newNode = newNodes[newCnt - (right + 1)];

					if (!refreshNode(oldNode, newNode))
					{
						break;
					}

					++right;
				}

				/**
				* Clone the new nodes
				*/
				var frag = document.createDocumentFragment(),
					i = left;

				while (i < (newCnt - right))
				{
					frag.appendChild(newNodes[i].cloneNode(true));
					++i;
				}

				/**
				* Remove the old dirty nodes in the middle of the tree
				*/
				i = oldCnt - right;
				while (--i >= left)
				{
					oldEl.removeChild(oldNodes[i]);
				}

				/**
				* If we haven't skipped any nodes to the right, we can just append the fragment
				*/
				if (!right)
				{
					oldEl.appendChild(frag);
				}
				else
				{
					oldEl.insertBefore(frag, oldEl.childNodes[left]);
				}
			}

			/**
			* Update given node oldNode to make it match newNode
			*
			* @param {!HTMLElement} oldNode
			* @param {!HTMLElement} newNode
			* @return boolean TRUE if the nodes were made to match, FALSE otherwise
			*/
			function refreshNode(oldNode, newNode)
			{
				if (oldNode.nodeName !== newNode.nodeName
				 || oldNode.nodeType !== newNode.nodeType)
				{
					return false;
				}

				// IE 7.0 doesn't seem to have Node.TEXT_NODE
				if (oldNode.nodeType === 3)
				{
					oldNode.nodeValue = newNode.nodeValue;
					return true;
				}

				if ((oldNode.isEqualNode && oldNode.isEqualNode(newNode))
				 || (ENABLE_IE_WORKAROUNDS && ENABLE_IE_WORKAROUNDS < 9 && oldNode.outerHTML && oldNode.outerHTML === newNode.outerHTML))
				{
					return true;
				}

				syncElementAttributes(oldNode, newNode);
				refreshElementContent(oldNode, newNode);

				return true;
			}

			/**
			* Make the set of attributes of given element oldEl match newEl's
			*
			* @param {!HTMLElement} oldEl
			* @param {!HTMLElement} newEl
			*/
			function syncElementAttributes(oldEl, newEl)
			{
				var oldCnt = oldEl.attributes.length,
					newCnt = newEl.attributes.length,
					i = oldCnt;

				while (--i >= 0)
				{
					var oldAttr = oldEl.attributes[i];

					if (ENABLE_IE_WORKAROUNDS && ENABLE_IE_WORKAROUNDS < 9 && !('hasAttributeNS' in newEl))
					{
						if (!(oldAttr.name in oldEl))
						{
							oldEl.removeAttribute(oldAttr.name);
						}
					}
					else if (!newEl.hasAttributeNS(oldAttr.namespaceURI, oldAttr.name))
					{
						oldEl.removeAttributeNS(oldAttr.namespaceURI, oldAttr.name);
					}
				}

				i = newCnt;
				while (--i >= 0)
				{
					var newAttr = newEl.attributes[i];

					if (ENABLE_IE_WORKAROUNDS && ENABLE_IE_WORKAROUNDS < 9 && !('getAttributeNS' in newEl))
					{
						if (newAttr.value !== oldEl.getAttribute(newAttr.name))
						{
							oldEl.setAttribute(newAttr.name, newAttr.value);
						}
					}
					else if (newAttr.value !== oldEl.getAttributeNS(newAttr.namespaceURI, newAttr.name))
					{
						oldEl.setAttributeNS(newAttr.namespaceURI, newAttr.name, newAttr.value);
					}
				}
			}

			refreshElementContent(target, frag);
		}
	}
}(/** XSL goes here **/);