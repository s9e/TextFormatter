matches.forEach(function(m)
{
	var bbcodeName = m[1][0].toUpperCase();
	if (!(bbcodeName in config.bbcodes))
	{
		return;
	}
	var bbcodeConfig = config.bbcodes[bbcodeName];

	// Use the configured tagName if available, or reuse the BBCode's name otherwise
	var tagName = bbcodeConfig.tagName || bbcodeName;

	/**
	* @type {!number} Position of the first character of current BBCode, which should be a [
	*/
	var lpos = m[0][1];

	/**
	* @type {!number} Position of the last character of current BBCode, starts as the position
	*                 of the "]", " ", "=", ":" or "/" character as per the plugin's regexp,
	*                 then advances towards the right as the BBCode is being parsed
	*/
	var rpos = lpos + m[0][0].length;

	// Check for an identifier
	//
	// Used to explicitly pair specific tags together, e.g.
	//   [code:123][code]type your code here[/code][/code:123]
	var bbcodeId;
	if (text.charAt(rpos) === ':')
	{
		// Capture the colon and the (0 or more) digits following it
		bbcodeId = /^:\d*/.exec(text.substr(rpos))[0];

		// Move past the suffix
		rpos += bbcodeId.length;
	}
	else
	{
		bbcodeId  = '';
	}

	// Test whether this is an end tag
	if (text.charAt(lpos + 1) === '/')
	{
		// Test whether the tag is properly closed and whether this tag has an identifier.
		// We skip end tags that carry an identifier because they're automatically added
		// when their start tag is processed
		if (text.charAt(rpos) === ']' && bbcodeId === '')
		{
			addEndTag(tagName, lpos, 1 + rpos - lpos);
		}

		return;
	}

	// This is a start tag, now we'll parse attributes
	var type       = Tag.START_TAG,
		attributes = {},
		wellFormed = false,
		firstPos   = rpos,
		attrName,
		attrValue,
		tag;

	// Add predefined attributes
	if (bbcodeConfig.predefinedAttributes)
	{
		for (attrName in bbcodeConfig.predefinedAttributes)
		{
			attributes[attrName] = bbcodeConfig.predefinedAttributes[attrName];
		}
	}

	while (rpos < textLen)
	{
		/** @type {!string} */
		var c = text.charAt(rpos);

		if (c === ' ')
		{
			++rpos;
			continue;
		}

		if (c === ']' || c === '/')
		{
			// We're closing this tag
			if (c === '/')
			{
				// Self-closing tag, e.g. [foo/]
				type = Tag.SELF_CLOSING_TAG;
				++rpos;

				if (rpos === textLen || text.charAt(rpos) !== ']')
				{
					// There isn't a closing bracket after the slash, e.g. [foo/
					return;
				}
			}

			// This tag is well-formed
			wellFormed = true;

			// Move past the right bracket
			++rpos;

			break;
		}

		// Capture the attribute name
		var spn = /^[-\w]*/.exec(text.substr(rpos))[0].length;

		if (spn)
		{
			if (rpos + spn >= textLen)
			{
				// The attribute name extends to the end of the text
				return;
			}

			attrName = text.substr(rpos, spn).toLowerCase();
			rpos += spn;

			if (text.charAt(rpos) !== '=')
			{
				// It's an attribute name not followed by an equal sign, ignore it
				continue;
			}
		}
		else if (c === '=' && rpos === firstPos)
		{
			// This is the default param, e.g. [quote=foo]. If there's no default attribute
			// set, we reuse the BBCode's name instead
			attrName = bbcodeConfig.defaultAttribute || bbcodeName.toLowerCase();
		}
		else
		{
			return;
		}

		// Move past the = and make sure we're not at the end of the text
		if (++rpos >= textLen)
		{
			return;
		}

		// Grab the first character after the equal sign
		c = text.charAt(rpos);

		// Test whether the value is in quotes
		if (c === '"' || c === "'")
		{
			// This is where the actual value starts
			var valuePos = rpos + 1;

			while (1)
			{
				// Move past the quote
				++rpos;

				// Look for the next quote
				rpos = text.indexOf(c, rpos);

				if (rpos < 0)
				{
					// No matching quote. Apparently that string never ends...
					return;
				}

				// Test for an odd number of backslashes before this character
				var n = 0;
				while (text.charAt(rpos - ++n) === '\\')
				{
				}

				if (n % 2)
				{
					// If n is odd, it means there's an even number of backslashes so
					// we can exit this loop
					break;
				}
			}

			// Unescape special characters ' " and \
			attrValue = text.substr(valuePos, rpos - valuePos).replace(/\\([\\'"])/g, '$1');

			// Skip past the closing quote
			++rpos;
		}
		else
		{
			// Capture everything after the equal sign up to whichever comes first:
			//  - whitespace followed by a slash and a closing bracket
			//  - a closing bracket, optionally preceded by whitespace
			//  - whitespace followed by another attribute (name followed by equal sign)
			//
			// NOTE: this is for compatibility with some forums (such as vBulletin it seems)
			//       that do not put attribute values in quotes, e.g.
			//       [quote=John Smith;123456] (quoting "John Smith" from post #123456)
			var match = /[^\]]*?(?=\s*(?: \/)?\]|\s+[-\w]+=)/.exec(text.substr(rpos));
			if (!match)
			{
				continue;
			}

			attrValue  = match[0];
			rpos  += attrValue.length;
		}

		attributes[attrName] = attrValue;
	}

	if (!wellFormed)
	{
		return;
	}

	if (type === Tag.START_TAG)
	{
		/**
		* @type {!Array} List of attributes whose value should be set to this tag's content
		*/
		var contentAttributes = [];

		// Record the names of attributes that need the content of this tag
		if (bbcodeConfig.contentAttributes)
		{
			bbcodeConfig.contentAttributes.forEach(function(attrName)
			{
				if (!(attrName in attributes))
				{
					contentAttributes.push(attrName);
				}
			});
		}

		// Test whether we need to look for this tag's end tag
		var endTag;
		if (contentAttributes.length || bbcodeId || bbcodeConfig.forceLookahead)
		{
			// Find the position of its end tag
			var match     = '[/' + bbcodeName + bbcodeId + ']',
				endTagPos = text.toUpperCase().indexOf(match, rpos);

			if (endTagPos < 0)
			{
				// We didn't find an end tag, did we *need* one?
				if (bbcodeId || bbcodeConfig.forceLookahead)
				{
					// No matching end tag, we skip this start tag
					return;
				}
			}
			else
			{
				// We found the end tag, we can use the content of this tag pair
				contentAttributes.forEach(function(attrName)
				{
					attributes[attrName] = text.substr(rpos, endTagPos - rpos);
				});

				// We create an end tag, which we will pair with this start tag
				endTag = addEndTag(tagName, endTagPos, match.length);
			}
		}

		// Create this start tag
		tag = addStartTag(tagName, lpos, rpos - lpos);

		// If an end tag was created, pair it with this start tag
		if (endTag)
		{
			tag.pairWith(endTag);
		}
	}
	else
	{
		tag = addSelfClosingTag(tagName, lpos, rpos - lpos);
	}

	// Add all attributes to the tag
	tag.setAttributes(attributes);
});