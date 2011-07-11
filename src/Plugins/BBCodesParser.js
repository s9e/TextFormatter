var tags = [],
	textLen = text.length;

matches.forEach(function(m)
{
	var bbcodeName = m[1][0].toUpperCase();

	if (!config.bbcodesConfig[bbcodeName])
	{
		// Not a known BBCode
		return;
	}

	var bbcodeConfig = config.bbcodesConfig[bbcodeName],
	    tagName      = bbcodeConfig.tagName;

	/**
	* Position of the first character of current BBCode, which should be a [
	*/
	var lpos = m[0][1];

	/**
	* Position of the last character of current BBCode, starts as the position of
	* the =, ] or : char, then moves to the right as the BBCode is parsed
	*/
	var rpos = lpos + m[0][0].length;

	/**
	* Attributes parsed from the text
	*/
	var attrs = {};

	/**
	* Check for BBCode suffix
	*
	* Used to skip the parsing of closing BBCodes, e.g.
	*   [code:1][code]type your code here[/code][/code:1]
	*
	*/
	var suffix = '';

	if (text.charAt(rpos) === ':')
	{
		/**
		* [code:1] or [/code:1]
		* suffix = ':1'
		*/
		suffix  = /^:[0-9]*/.exec(text.substr(rpos))[0];
		rpos   += suffix.length;
	}

	var type;

	if (m[0][0].charAt(1) === '/')
	{
		if (text.charAt(rpos) !== ']')
		{
			log('warning', {
				'pos'    : rpos,
				'len'    : 1,
				'msg'    : 'Unexpected character: expected %1$s found %2$s',
				'params' : [']', text.charAt(rpos)]
			});
			return;
		}

		type = END_TAG;
	}
	else
	{
		type = START_TAG;

		var wellFormed = false,
		    firstPos   = rpos;

		while (rpos < textLen)
		{
			c = text.charAt(rpos);

			if (c === ']' || c === '/')
			{
				/**
				* We're closing this tag
				*/
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
						return;
					}

					c = text.charAt(rpos);
					if (c !== ']')
					{
						log('warning', {
							'pos'    : rpos,
							'len'    : 1,
							'msg'    : 'Unexpected character: expected %1$s found %2$s',
							'params' : [']', c]
						});
						return;
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

			/**
			* Capture the attribute name
			*/
			var attrName = /^[a-z_0-9]*/i.exec(text.substr(rpos))[0].toLowerCase();

			if (attrName)
			{
				if (rpos + attrName.length >= textLen)
				{
					log('debug', {
						'pos' : rpos,
						'len' : attrName.length,
						'msg' : 'Attribute name seems to extend till the end of text'
					});
					return;
				}

				rpos += attrName.length;
			}
			else
			{
				if (c === '='
				 && rpos === firstPos)
				{
					/**
					* [quote=
					*
					* This is the default param. If there's no default param, we issue a
					* warning and reuse the BBCode's name instead.
					*/
					if (bbcodeConfig.defaultAttr)
					{
						attrName = bbcodeConfig.defaultAttr;
					}
					else
					{
						attrName = bbcodeName.toLowerCase();

						log('debug', {
							'pos'    : rpos,
							'len'    : 1,
							'msg'    : 'BBCode %1$s does not have a default attribute, using BBCode name as attribute name',
							'params' : [bbcodeName]
						});
					}
				}
				else
				{
					log('warning', {
						'pos'    : rpos,
						'len'    : 1,
						'msg'    : 'Unexpected character %s',
						'params' : [c]
					});
					return;
				}
			}

			if (text.charAt(rpos) !== '=')
			{
				/**
				* It's an attribute name not followed by an equal sign, let's just
				* ignore it
				*/
				continue;
			}

			/**
			* Move past the = and make sure we're not at the end of the text
			*/
			if (++rpos >= textLen)
			{
				log('debug', {
					'msg' : 'Attribute definition seems to extend till the end of text'
				});
				return;
			}

			c = text.charAt(rpos);
			if (c === '"' || c === "'")
			{
				var valuePos = rpos + 1;

				while (++rpos < textLen)
				{
					rpos = text.indexOf(c, rpos);

					if (rpos === -1)
					{
						/**
						* No matching quote, apparently that string never ends...
						*/
						log('warning', {
							'pos' : valuePos - 1,
							'len' : 1,
							'msg' : 'Could not find matching quote'
						});
						return;
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

				value = text.substr(valuePos, rpos - valuePos).replace(/\\([\\'"])/g, '$1');

				// Skip past the closing quote
				++rpos;
			}
			else
			{
				value = /^[^\] \n\r]*/.exec(text.substr(rpos))[0];
				rpos += value.length;
			}

			attrs[attrName] = value;
		}

		if (!wellFormed)
		{
			return;
		}

		var usesContent = false;

		if (type === START_TAG
		 && bbcodeConfig.contentAttr
		 && attrs[bbcodeConfig.contentAttr] === undefined)
		{
			/**
			* Capture the content of that tag and use it as attribute value
			*/
			var pos = text.toUpperCase().indexOf('[/' + bbcodeName + suffix + ']', rpos);

			if (pos > -1)
			{
				attrs[bbcodeConfig.contentAttr]
					= text.substr(1 + rpos, pos - (1 + rpos));

				usesContent = true;
			}
		}
	}

	if (type === START_TAG
	 && !usesContent
	 && bbcodeConfig.autoClose)
	{
		var endTag = '[/' + bbcodeName + suffix + ']';

		/**
		* Make sure that the start tag isn't immediately followed by an endtag
		*/
		if (text.substr(1 + rpos, endTag.length).toUpperCase() !== endTag)
		{
			type = SELF_CLOSING_TAG;
		}
	}

	tags.push({
		name    : tagName,
		pos     : lpos,
		len     : rpos + 1 - lpos,
		type    : type,
		tagMate : (suffix > '') ? suffix.substr(1) : '',
		attrs   : attrs
	});
});

return tags;