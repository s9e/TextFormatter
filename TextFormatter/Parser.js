Parser = function()
{
	var text,

		tagStack,
		tags,

		passes,
		filters,
		xslt,

		log = {
			debug:   [],
			warning: [],
			error:   []
		},

		/** @const */
		START_TAG         = 1,
		/** @const */
		END_TAG           = 2,
		/** @const */
		SELF_CLOSING_TAG  = 3,

		/** @const */
		TRIM_CHARLIST = " \n\r\t\0\x0B",

		rtrimRegExp = new RegExp('[' + TRIM_CHARLIST + ']*$'),
		ltrimRegExp = new RegExp('^[' + TRIM_CHARLIST + ']*');

	//==============================================================================================
	// Javascript-specific code
	//==============================================================================================

	function getMatches(regexpStr)
	{
		var regexp = new RegExp(regexpStr.substr(1, regexpStr.lastIndexOf(regexpStr.charAt(0)) - 1), 'gi'),
			ret    = [],
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

	//==============================================================================================
	// Stuff that exists in the PHP parser
	//==============================================================================================

	function executePasses()
	{
		var passName,
			passOrder = -1;

		for (passName in passes)
		{
			var passConfig = passes[passName],
				matches    = [];

			if (typeof passConfig['regexp'] != 'undefined')
			{
				var regexps = passConfig['regexp'],
					isArray = regexps instanceof Array,

					i   = -1,
					cnt = 0;

				if (!isArray)
				{
					regexps = [regexps];
				}

				while (++i < regexps.length && cnt < passConfig['limit'])
				{
					var m = getMatches(regexps[i]);

					if (cnt + m.length > passConfig['limit'])
					{
						Parser.log('warning', {
							msg:    passName + ' limit exceeded. Only the first %s matches will be processed',
							params: [passConfig['limit']]
						});
					}

					matches.push(m.slice(0, passConfig['limit'] - cnt));
					cnt += m.length;
				}

				if (!isArray)
				{
					matches = matches[0];
				}
			}

			var tags = Parser['get' + passName + 'Tags'](text, passConfig, matches),
				i    = tags.length;

			while (--i >= 0)
			{
				tagStack.push(tags[i]);
			}
		}
	}

	function normalizeTags()
	{
		var bbcodes = passes['BBCode']['bbcodes'],
			aliases = passes['BBCode']['aliases'],

			i        = -1,
			cnt      = tagStack.length,
			newStack = [];

		while (++i < cnt)
		{
			var tag = tagStack[i];

			/**
			* Normalize the tag name
			*/
			if (typeof bbcodes[tag['name']])
			{
				var bbcodeId = tag['name'].toUpperCase();

				if (typeof aliases[bbcodeId] == 'undefined')
				{
					Parser.log('debug', {
						'pos'    : tag['pos'],
						'msg'    : 'Removed unknown BBCode %1$s from pass %2$s',
						'params' : [tag['name'], tag['pass']]
					});

					continue;
				}

				tag['name'] = aliases[bbcodeId];
				newStack[i] = tag;
			}
		}

		tagStack = newStack;
	}

	function sortTags()
	{
		tagStack.sort(function(a, b)
		{
			return (b['pos']  - a['pos'])
			    || (a['type'] - b['type'])
			    || (b['pass'] - a['pass']);
		});
	}

	function addTrimmingInfoToTag(tag, offset)
	{
		var bbcode = passes['BBCode']['bbcodes'][tag['name']];

		/**
		* Original: "  [b]  -text-  [/b]  "
		* Matches:  "XX[b]  -text-XX[/b]  "
		*/
		if ((tag['type']  &  START_TAG && typeof bbcode['trim_before']   != 'undefined' && bbcode['trim_before'])
		 || (tag['type'] === END_TAG   && typeof bbcode['rtrim_content'] != 'undefined' && bbcode['rtrim_content']))
		{
			tag['trim_before']  = rtrimRegexp.exec(text.substr(0, offset))[0].length;
			tag['len']         += tag['trim_before'];
			tag['pos']         -= tag['trim_before'];
		}

		/**
		* Move the cursor past the tag
		*/
		offset = tag['pos'] + tag['len'];

		/**
		* Original: "  [b]  -text-  [/b]  "
		* Matches:  "  [b]XX-text-  [/b]XX"
		*/
		if ((tag['type'] === START_TAG && typeof bbcode['ltrim_content'] != 'undefined' && bbcode['ltrim_content'])
		 || (tag['type']  &  END_TAG   && typeof bbcode['trim_after']    != 'undefined' && bbcode['trim_after']))
		{
			tag['trim_after']  = ltrimRegexp.exec(text.substr(offset))[0].length;
			tag['len']        += tag['trim_after'];
		}
	}

	function appendTag(tag)
	{
		var offset = 0;

		if (tags.length)
		{
			/**
			* The left boundary is right after the last tag
			*/
			var lastTag = tags[tags.length - 1];

			offset  = lastTag['pos'] + lastTag['len'];
		}

		addTrimmingInfoToTag(tag, offset);

		tags.push(tag);
	}

	function processTags()
	{
		if (!tagStack.length)
		{
			return;
		}

		//======================================================================
		// Time to get serious
		//======================================================================

		var aliases = passes['BBCode']['aliases'],
			bbcodes = passes['BBCode']['bbcodes'],

			bbcodeStack = [],

			allowed  = aliases,
			cntTotal = {},
			cntOpen  = {},

			openTags = [],

			pos = 0;

		for (var k in allowed)
		{
			cntTotal[k] = 0;
			cntOpen[k]  = 0;
		}

		do
		{
			try
			{
				var tag = tagStack.pop();

				if (pos > tag['pos'])
				{
					Parser.log('debug', {
						'pos' : tag['pos'],
						'msg' : 'Tag skipped'
					});
					continue;
				}

				var bbcodeId = tag['name'],
					bbcode   = bbcodes[bbcodeId],
					suffix   = (typeof tag['suffix'] != 'undefined') ? tag['suffix'] : '';

				//==================================================================
				// Start tag
				//==================================================================

				if (tag['type'] & START_TAG)
				{
					//==============================================================
					// Check that this BBCode is allowed here
					//==============================================================

					if (typeof bbcode['close_parent'] == 'object' && bbcode['close_parent'].length)
					{
						/**
						* Oh, wait, we may have to close its parent first
						*/
						var lastBBCode = bbcodeStack[bbcodeStack.length - 1],
							parentBBCodeId;

						for (var k in bbcode['close_parent'])
						{
							parentBBCodeId = bbcode['close_parent'][k];

							if (lastBBCode['bbcode_id'] === parentBBCodeId)
							{
								/**
								* So we do have to close that parent. First we reinsert current tag... 
								*/
								tagStack.push(tag);

								/**
								* ...then we create a new end tag which we put on top of the stack
								*/
								tag = {
									'pos'    : tag['pos'],
									'name'   : parentBBCodeId,
									'suffix' : lastBBCode['suffix'],
									'len'    : 0,
									'type'   : END_TAG
								};

								addTrimmingInfoToTag(tag, pos);
								tagStack.push(tag);

								throw 'next tag';
							}
						}
					}

					if (bbcode['nesting_limit'] <= cntOpen[bbcodeId]
					 || bbcode['tag_limit']     <= cntTotal[bbcodeId])
					{
						continue;
					}

					if (allowed[bbcodeId] == 'undefined')
					{
						Parser.log('debug', {
							'pos'    : tag['pos'],
							'msg'    : 'BBCode %s is not allowed in this context',
							'params' : [bbcodeId]
						});
						continue;
					}

					if (typeof bbcode['require_parent'] != 'undefined')
					{
						var lastBBCode = bbcodeStack[bbcodeStack.length - 1];

						if (!lastBBCode
						 || lastBBCode['bbcode_id'] !== bbcode['require_parent'])
						{
							Parser.log('debug', {
								'pos'    : tag['pos'],
								'msg'    : 'BBCode %1$s requires %2$s as parent',
								'params' : [bbcodeId, bbcode['require_parent']]
							});

							continue;
						}
					}

					if (typeof bbcode['require_ascendant'] != 'undefined')
					{
						for (var k in bbcode['require_ascendant'])
						{
							var ascendant = bbcode['require_ascendant'][k]
							if (typeof cntOpen[ascendant] == 'undefined'
							 || !cntOpen[ascendant])
							{
								Parser.log('debug', {
									'pos'    : tag['pos'],
									'msg'    : 'BBCode %1$s requires %2$s as ascendant',
									'params' : [bbcodeId, ascendant]
								});
								throw 'next tag';
							}
						}
					}

					if (typeof bbcode['params'] != 'undefined')
					{
						/**
						* Check for missing required params
						*/
						var paramName, paramConf;
						for (paramName in bbcode['params'])
						{
							paramConf = bbcode['params'][paramName];

							if (!paramConf['is_required']
							 || typeof tag['params'][paramName] != 'undefined')
							{
								continue;
							}

							Parser.log('error', {
								'pos'    : tag['pos'],
								'msg'    : 'Missing param %s',
								'params' : [paramName]
							});

							throw 'next tag';
						}
/*
						foreach ($tag['params'] as $k => &$v)
						{
							$msgs = array();
							$v    = $this->filter($v, $bbcode['params'][$k]['type'], $msgs);

							foreach ($msgs as $type => $_msgs)
							{
								foreach ($_msgs as $msg)
								{
									$msg['pos'] = $tag['pos'];
									Parser.log($type, $msg);
								}
							}

							if ($v === false)
							{
								Parser.log('error', array(
									'pos'    => $tag['pos'],
									'msg'    => 'Invalid param %s',
									'params' => array($k)
								));

								if ($bbcode['params'][$k]['is_required'])
								{
									// Skip this tag
									continue 2;
								}

								unset($tag['params'][$k]);
							}
						}
*/
					}

					//==============================================================
					// Ok, so we have a valid BBCode
					//==============================================================

					appendTag(tag);

					pos = tag['pos'] + tag['len'];

					++cntTotal[bbcodeId];

					if (tag['type'] & END_TAG)
					{
						continue;
					}

					++cntOpen[bbcodeId];

					if (typeof openTags[bbcodeId + suffix] != 'undefined')
					{
						++openTags[bbcodeId + suffix];
					}
					else
					{
						openTags[bbcodeId + suffix] = 1;
					}

					bbcodeStack.push({
						'bbcode_id' : bbcodeId,
						'suffix'	: suffix,
						'allowed'   : allowed
					});

					for (var k in allowed)
					{
						if (typeof bbcode['allow'][k] == 'undefined')
						{
							// TODO: test this
							delete allowed[k];
						}
					}
				}

				//==================================================================
				// End tag
				//==================================================================

				if (tag['type'] & END_TAG)
				{
					if (typeof openTags[bbcodeId + suffix] == 'undefined')
					{
						/**
						* This is an end tag but there's no matching start tag
						*/
						Parser.log('debug', {
							'pos'    : tag['pos'],
							'msg'    : 'Could not find a matching start tag for BBCode %s',
							'params' : [bbcodeId + suffix]
						});
						continue;
					}

					pos = tag['pos'] + tag['len'];

					do
					{
						cur     = bbcodeStack.pop();
						allowed = cur['allowed'];

						--cntOpen[cur['bbcode_id']];
						--openTags[cur['bbcode_id'] + cur['suffix']];

						if (cur['bbcode_id'] !== bbcodeId)
						{
							appendTag({
								'name' : cur['bbcode_id'],
								'pos'  : tag['pos'],
								'len'  : 0,
								'type' : END_TAG
							});
						}
						break;
					}
					while (1);

					appendTag(tag);
				}
			}
			catch (e)
			{
				if (e != 'next tag')
				{
					throw e;
				}
			}
		}
		while (tagStack.length);
	}

	function escape(str)
	{
	}

	function asXML()
	{
		var pos = 0,
			i   = -1,
			cnt = tags.length;
	}

	function asDOM()
	{
		var stack = [],
			pos   = 0,
			i     = -1,
			cnt   = tags.length,
			DOM   = document.implementation.createDocument('', (cnt) ? 'rt' : 'pt', null),
			el    = DOM.documentElement;

		while (++i < cnt)
		{
			var tag = tags[i],
				content = text.substr(tag['pos'] + tag['trim_before'], tag['len'] - tag['trim_before'] - tag['trim_after']);

			if (tag['pos'] > pos)
			{
				el.appendChild(DOM.createTextNode(text.substr(pos, tag['pos'] - pos)));
			}

			pos = tag['pos'] + tag['len'];

			if (tag['type'] & START_TAG)
			{
				stack.push(el);
				el = el.appendChild(DOM.createElement(tag['name']));

				for (var attrName in tag['params'])
				{
					el.setAttribute(attrName, tag['params'][attrName]);
				}

				if (content > '')
				{
					if (tag['type'] == SELF_CLOSING_TAG)
					{
						el.textContent = content;
					}
					else
					{
						el.appendChild(DOM.createElement('st')).textContent = content;
					}
				}
			}
			else
			{
				if (content > '')
				{
					el.appendChild(DOM.createElement('et')).textContent = content;
				}

				el = stack.pop();
			}
		}

		el.appendChild(DOM.createTextNode(text.substr(pos)));

//		console.dir(el);

		return DOM;
	}

	return {
		'setConfig': function(config)
		{
			passes  = config['passes'];
			filters = config['filters'];
			xslt    = new XSLTProcessor();

			xslt.importStylesheet(new DOMParser().parseFromString(config['xsl'], 'text/xml'));
		},

		'log': function(type, entry)
		{
			log[type].push(entry);
		},

		'parse': function(_text)
		{
			text = _text;

			tagStack = [];
			tags     = [];

			executePasses();
			normalizeTags();
			sortTags();
			processTags();

			var frag = xslt.transformToFragment(asDOM(),document);

//			console.dir(frag);
		},

		'getBBCodeTags': function(text, config, matches)
		{
			var tags = [],

				bbcodes = config['bbcodes'],
				aliases = config['aliases'],

				textLen = text.length,

				i   = -1,
				cnt = matches.length;

			while (++i < cnt)
			{
				try
				{
					var m = matches[i],

						lpos = m[0][1],
						rpos = lpos + m[0][0].length,

						suffix = '';

					/**
					* Check for BBCode suffix
					*
					* Used to skip the parsing of closing BBCodes, e.g.
					*   [code:1][code]type your code here[/code][/code:1]
					*
					*/
					if (text.charAt(rpos) === ':')
					{
						/**
						* [code:1] or [/code:1]
						* suffix = ':1'
						*/
						suffix  = /^[0-9]*/.exec(text.substr(rpos));
						rpos   += suffix.length;
					}

					var alias = m[1][0].toUpperCase();

					if (typeof aliases[alias] == 'undefined')
					{
						// Not a known BBCode or alias
						continue;
					}

					var bbcodeId = aliases[alias],
						bbcode   = bbcodes[bbcodeId],
						params   = {};

					if (bbcode['internal_use'])
					{
						/**
						* This is theorically impossible, as the regexp does not contain internal BBCodes.
						*/
						if (m[0][0][1] !== '/')
						{
							/**
							* We only warn about starting tags, no need to raise 2 warnings per pair
							*/
							Parser.log('warning', {
								'pos'    : lpos,
								'msg'    : 'BBCode %s is for internal use only',
								'params' : [bbcodeId]
							});
						}
						continue;
					}

					var type;

					if (m[0][0][1] === '/')
					{
						if (text.charAt(rpos) !== ']')
						{
							Parser.log('warning', {
								'pos'    : rpos,
								'msg'    : 'Unexpected character %s',
								'params' : [text[rpos]]
							});
							continue;
						}

						type = END_TAG;
					}
					else
					{
						type = START_TAG;

						var wellFormed = false,
							param      = false,
							value;

						if (text.charAt(rpos) === '=')
						{
							/**
							* [quote=
							*
							* Set the default param. If there's no default param, we issue a warning and
							* reuse the BBCode's name instead
							*/
							if (typeof bbcode['default_param'] != 'undefined')
							{
								param = bbcode['default_param'];
							}
							else
							{
								param = bbcodeId.toLowerCase();

								Parser.log('debug', {
									'pos'    : rpos,
									'msg'    : "BBCode %s does not have a default param, using BBCode's name as param name",
									'params' : [bbcodeId]
								});
							}

							++rpos;
						}

						var lastPos = 0;
						while (rpos < textLen)
						{
							if (rpos <= lastPos)
							{
								throw 'Infinite loop detected';
							}
							lastPos = rpos;

							var c = text.charAt(rpos);

							if (c === ']' || c === '/')
							{
								/**
								* We're closing this tag
								*/
								if (param !== false)
								{
									/**
									* [quote=]
									* [quote username=]
									*/
									Parser.log('warning', {
										'pos'    : rpos,
										'msg'    : 'Unexpected character %s',
										'params' : [c]
									});
									throw 'next match';
								}

								if (c === '/')
								{
									/**
									* Self-closing tag, e.g. [foo/]
									*/
									type = SELF_CLOSING_TAG;
									++rpos;

									if (rpos === textLen)
									{
										// text ends with [some tag/
										throw 'next match';
									}

									c = text.charAt(rpos);
									if (c !== ']')
									{
										Parser.log('warning', {
											'pos'    : rpos,
											'msg'    : 'Unexpected character: expected ] found %s',
											'params' : [c]
										});
										throw 'next match';
									}
								}

								wellFormed = true;
								break;
							}

							if (c === ' ')
							{
								++rpos;
								continue;
							}

							if (param === false)
							{
								/**
								* Capture the param name
								*/
								param = /^[a-z_0-9]*/i.exec(text.substr(rpos)).toLowerCase();

								if (param == '')
								{
									Parser.log('warning', {
										'pos'    : rpos,
										'msg'    : 'Unexpected character %s',
										'params' : [c]
									});
									throw 'next match';
								}

								if (rpos + param.length >= textLen)
								{
									Parser.log('debug', {
										'pos' : rpos,
										'msg' : 'Param name seems to extend till the end of $text'
									});
									throw 'next match';
								}

								rpos += param.length;

								if (text.charAt(rpos) !== '=')
								{
									Parser.log('warning', {
										'pos'    : rpos,
										'msg'    : 'Unexpected character %s',
										'params' : [text.charAt(rpos)]
									});
									throw 'next match';
								}

								++rpos;
								continue;
							}

							if (c === '"' || c === "'")
							{
								var valuePos = rpos + 1;

								while (++rpos < textLen)
								{
									rpos = text.indexOf(c, rpos);

									if (rpos == -1)
									{
										/**
										* No matching quote, apparently that string never ends...
										*/
										Parser.log('error', {
											'pos' : valuePos - 1,
											'msg' : 'Could not find matching quote'
										});
										throw 'next match';
									}

									if (text.charAt(rpos - 1) === '\\')
									{
										var n = 1;
										do
										{
											++n;
										}
										while (text.charAt(rpos - n) === '\\');

										if (n % 2 === 0)
										{
											continue;
										}
									}

									break;
								}

								value = text.substr(valuePos, rpos - valuePos).replace(/\\(.)/g, '$1');

								// Skip past the closing quote
								++rpos;
							}
							else
							{
								value = /^[^\] \n\r]*/.exec(text.substr(rpos))[0];
								rpos += value.length;
							}

							if (typeof bbcode['params'][param] != 'undefined')
							{
								/**
								* We only keep params that exist in the BBCode's definition
								*/
								params[param] = value;
							}

							param = false;
						}

						if (!wellFormed)
						{
							continue;
						}

						if (type === START_TAG
						 && typeof bbcode['default_param'] != 'undefined'
						 && typeof params[bbcode['default_param']] == 'undefined'
						 && bbcode['content_as_param'])
						{
							var pos = text.toUpperCase().indexOf('[/' + bbcodeId + suffix + ']', rpos);

							if (pos > -1)
							{
								params[bbcode['default_param']]
									= text.substr(1 + rpos, pos - (1 + rpos));
							}
						}
					}

					tags.push({
						'name'   : bbcodeId,
						'pos'    : lpos,
						'len'    : rpos + 1 - lpos,
						'type'   : type,
						'suffix' : suffix,
						'params' : params
					});
				}
				catch (e)
				{
					if (e !== 'next match')
					{
						throw e;
					}
				}
			}

			return tags;
		}
	}
}();