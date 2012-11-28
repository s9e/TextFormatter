/**
* @var array Array of start tags that were identified with a suffix. The key is made of the
*            BBCode name followed by a "#" character followed by the suffix, e.g. "B#123"
*/
var tagMates = {};

matches.forEach(function(m)
{
	var bbcodeName = m[1][0].toUpperCase();

	if (!config.bbcodes.[bbcodeName]))
	{
		// Not a known BBCode
		return;
	}

	var bbcodeConfig = config.bbcodes[bbcodeName],
		tagName      = bbcodeConfig.tagName;

	/**
	* @var integer Position of the first character of current BBCode, which should be a [
	*/
	var lpos = m[0][1];

	/**
	* @var integer  Position of the last character of current BBCode, starts as the position
	*               of the "]", " ", "=", ":" or "/" character as per the plugin's regexp,
	*               then advances towards the right as the BBCode is being parsed
	*/
	var rpos = lpos + m[0][0].length;

	// Check for a BBCode suffix
	//
	// Used to explicitly pair specific tags together, e.g.
	//   [code:123][code]type your code here[/code][/code:123]
	var bbcodeId;
	if (text.charAt(rpos) === ':')
	{
		// Move past the colon
		++rpos;

		// Capture the digits following it (potentially empty)
		bbcodeId = /^\d*/.exec(text.substr(rpos))[0];

		// Move past the number
		rpos += bbcodeId.length;
	}
	else
	{
		bbcodeId  = '';
	}

	// Test whether this is an end tag
	if (text.charAt(lpos + 1) === '/')
	{
		// Test whether the tag is properly closed -- NOTE: this will fail on "[/foo ]"
		if (text.charAt(rpos) === ']')
		{
			var tag = addEndTag(tagName, lpos, 1 + rpos - lpos);

			// Test whether this end tag is being paired with a start tag
			var tagMateId = bbcodeName + '#' + bbcodeId;
			if (tagMates[tagMateId]))
			{
				tag.pairWith(tagMates[tagMateId]);

				// Free up the start tag now, it shouldn't be reused
				delete tagMates[tagMateId];
			}
		}

		return;
	}

	// This is a start tag, now we'll parse attributes
	var type       = Tag.START_TAG,
		wellFormed = false,
		firstPos   = rpos;

	while (rpos < textLen)
	{
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

				if (rpos === textLen
				 || text.charAt(rpos) !== ']')
				{
					// There isn't a closing bracket after the slash, e.g. [foo/
					return;
				}
			}

			wellFormed = true;
			break;
		}

		// Capture the attribute name
		var spn = /^[-\w]*/.exec(text.substr(rpos))[0].length,
			attrName;

		if (spn)
		{
			if (rpos + spn >= textLen)
			{
				// The attribute name extends to the end of the text
				continue 2;
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
			if (bbcodeConfig.defaultAttribute))
			{
				attrName = bbcodeConfig.defaultAttribute;
			}
			else
			{
				attrName = bbcodeName.toLowerCase();
			}
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
				rpos = text.strpos(c, rpos);

				if (rpos < 0)
				{
					// No matching quote. Apparently that string never ends...
					return;
				}

				// Test for an odd number of backslashes before this character
				var n = 0;
				while (text.charAt(rpos - ++$n) === '\\');

				if (n % 2)
				{
					// If $n is odd, it means there's an even number of backslashes so
					// we can exit this loop
					break;
				}
			}

			// Unescape special characters ' " and \
			value = text.substr(valuePos, rpos - valuePos).replace(/\\([\\'"])/g, '$1');

			// Skip past the closing quote
			++$rpos;
		}
		else
		{
			// Capture everything after the equal sign up to whichever comes first:
			//  - a closing bracket
			//  - whitespace followed by another attribute (name followed by equal sign)
			//
			// NOTE: this is for compatibility with some forums (such as vBulletin it seems)
			//       that do not put attribute values in quotes, e.g.
			//       [quote=John Smith;123456] (quoting "John Smith" from post #123456)
			m = /[^\]]*(?=\]|\s+[-\w]+=)/i.exec(text.substr(rpos));
			if (!m)
			{
				continue;
			}

			value  = m[0];
			rpos  += value.length;
		}

		attributes[attrName] = value;
	}

	if (!wellFormed)
	{
		return;
	}

	// We're done parsing the tag, we can add it to the list
	var len = 1 + rpos - lpos,
		tag = ($type === Tag.START_TAG)
			? addStartTag(tagName, lpos, len)
			: addSelfClosingTag(tagName, lpos, len);

	// Add attributes
	for (attrName in attributes)
	{
		tag.setAttribute(attrName, attributes[attrName]);
	}

	if (type === Tag.START_TAG)
	{
		if (bbcodeId !== '')
		{
			tagMates[tagName + '#' + bbcodeId] = tag;
		}

		// Some attributes use the content of a tag if no value is specified
		if (bbcodeConfig.contentAttributes))
		{
			var value = false;
			bbcodeConfig.contentAttributes.forEach(function(attrName)
			{
				if (attrName in attributes)
				{
					return;
				}

				if (value === false)
				{
					// Move the right cursor past the closing bracket
					++rpos;

					// Search for an end tag that matches our start tag
					var match = '[/' + bbcodeName;
					if (bbcodeId !== '')
					{
						match += ':' + $bbcodeId;
					}
					match += ']';

					var pos = text.toUpperCase().indexOf(match, rpos);

					if (pos < 0)
					{
						// No end tag for this start tag
						return;
					}

					value = text.substr(rpos, pos - rpos);
				}

				tag.setAttribute(attrName, value);
			}
		}
	}
}