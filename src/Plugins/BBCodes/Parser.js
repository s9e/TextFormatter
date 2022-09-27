/**
* @type {!Object} Attributes of the BBCode being parsed
*/
let attributes;

/**
* @type {!Object} Configuration for the BBCode being parsed
*/
let bbcodeConfig;

/**
* @type {string} Name of the BBCode being parsed
*/
let bbcodeName;

/**
* @type {string} Suffix of the BBCode being parsed, including its colon
*/
let bbcodeSuffix;

/**
* @type {number} Position of the cursor in the original text
*/
let pos;

/**
* @type {number} Position of the start of the BBCode being parsed
*/
let startPos;

/**
* @type {number} Length of the text being parsed
*/
let textLen = text.length;

/**
* @type {string} Text being parsed, normalized to uppercase
*/
let uppercaseText = '';

matches.forEach((m) =>
{
	bbcodeName = m[1][0].toUpperCase();
	if (!(bbcodeName in config.bbcodes))
	{
		return;
	}
	bbcodeConfig = config.bbcodes[bbcodeName];
	startPos     = m[0][1];
	pos          = startPos + m[0][0].length;

	try
	{
		parseBBCode();
	}
	catch (e)
	{
		// Do nothing
	}
});

/**
* Add the end tag that matches current BBCode
*
* @return {!Tag}
*/
function addBBCodeEndTag()
{
	return addEndTag(getTagName(), startPos, pos - startPos);
}

/**
* Add the self-closing tag that matches current BBCode
*
* @return {!Tag}
*/
function addBBCodeSelfClosingTag()
{
	let tag = addSelfClosingTag(getTagName(), startPos, pos - startPos);
	tag.setAttributes(attributes);

	return tag;
}

/**
* Add the start tag that matches current BBCode
*
* @return {!Tag}
*/
function addBBCodeStartTag()
{
	let prio = (bbcodeSuffix !== '') ? -10 : 0,
		tag = addStartTag(getTagName(), startPos, pos - startPos, prio);
	tag.setAttributes(attributes);

	return tag;
}

/**
* Parse the end tag that matches given BBCode name and suffix starting at current position
*
* @return {?Tag}
*/
function captureEndTag()
{
	if (!uppercaseText)
	{
		uppercaseText = text.toUpperCase();
	}
	let match     = '[/' + bbcodeName + bbcodeSuffix + ']',
		endTagPos = uppercaseText.indexOf(match, pos);
	if (endTagPos < 0)
	{
		return null;
	}

	return addEndTag(getTagName(), endTagPos, match.length);
}

/**
* Get the tag name for current BBCode
*
* @return {string}
*/
function getTagName()
{
	// Use the configured tagName if available, or reuse the BBCode's name otherwise
	return bbcodeConfig.tagName || bbcodeName;
}

/**
* Parse attributes starting at current position
*/
function parseAttributes()
{
	let firstPos = pos, attrName;
	attributes = {};
	while (pos < textLen)
	{
		let c = text[pos];
		if (" \n\t".indexOf(c) > -1)
		{
			++pos;
			continue;
		}
		if ('/]'.indexOf(c) > -1)
		{
			return;
		}

		// Capture the attribute name
		let spn = /^[-\w]*/.exec(text.substring(pos, pos + 100))[0].length;
		if (spn)
		{
			attrName = text.substring(pos, pos + spn).toLowerCase();
			pos += spn;
			if (pos >= textLen)
			{
				// The attribute name extends to the end of the text
				throw '';
			}
			if (text[pos] !== '=')
			{
				// It's an attribute name not followed by an equal sign, ignore it
				continue;
			}
		}
		else if (c === '=' && pos === firstPos)
		{
			// This is the default param, e.g. [quote=foo]
			attrName = bbcodeConfig.defaultAttribute || bbcodeName.toLowerCase();
		}
		else
		{
			throw '';
		}

		// Move past the = and make sure we're not at the end of the text
		if (++pos >= textLen)
		{
			throw '';
		}

		attributes[attrName] = parseAttributeValue();
	}
}

/**
* Parse the attribute value starting at current position
*
* @return {string}
*/
function parseAttributeValue()
{
	// Test whether the value is in quotes
	if (text[pos] === '"' || text[pos] === "'")
	{
		return parseQuotedAttributeValue();
	}

	// Capture everything up to whichever comes first:
	//  - an endline
	//  - whitespace followed by a slash and a closing bracket
	//  - a closing bracket, optionally preceded by whitespace
	//  - whitespace followed by another attribute (name followed by equal sign)
	//
	// NOTE: this is for compatibility with some forums (such as vBulletin it seems)
	//       that do not put attribute values in quotes, e.g.
	//       [quote=John Smith;123456] (quoting "John Smith" from post #123456)
	let match     = /(?:[^\s\]]|[ \t](?!\s*(?:[-\w]+=|\/?\])))*/.exec(text.substring(pos)),
		attrValue = match[0];
	pos += attrValue.length;

	return attrValue;
}

/**
* Parse current BBCode
*/
function parseBBCode()
{
	parseBBCodeSuffix();

	// Test whether this is an end tag
	if (text[startPos + 1] === '/')
	{
		// Test whether the tag is properly closed and whether this tag has an identifier.
		// We skip end tags that carry an identifier because they're automatically added
		// when their start tag is processed
		if (text[pos] === ']' && bbcodeSuffix === '')
		{
			++pos;
			addBBCodeEndTag();
		}

		return;
	}

	// Parse attributes and fill in the blanks with predefined attributes
	parseAttributes();
	if (bbcodeConfig.predefinedAttributes)
	{
		for (let attrName in bbcodeConfig.predefinedAttributes)
		{
			if (!(attrName in attributes))
			{
				attributes[attrName] = bbcodeConfig.predefinedAttributes[attrName];
			}
		}
	}

	// Test whether the tag is properly closed
	if (text[pos] === ']')
	{
		++pos;
	}
	else
	{
		// Test whether this is a self-closing tag
		if (text.substring(pos, pos + 2) === '/]')
		{
			pos += 2;
			addBBCodeSelfClosingTag();
		}

		return;
	}

	// Record the names of attributes that need the content of this tag
	let contentAttributes = [];
	if (bbcodeConfig.contentAttributes)
	{
		bbcodeConfig.contentAttributes.forEach((attrName) =>
		{
			if (!(attrName in attributes))
			{
				contentAttributes.push(attrName);
			}
		});
	}

	// Look ahead and parse the end tag that matches this tag, if applicable
	let requireEndTag = (bbcodeSuffix || bbcodeConfig.forceLookahead),
		endTag = (requireEndTag || contentAttributes.length) ? captureEndTag() : null;
	if (endTag)
	{
		contentAttributes.forEach((attrName) =>
		{
			attributes[attrName] = text.substring(pos, endTag.getPos());
		});
	}
	else if (requireEndTag)
	{
		return;
	}

	// Create this start tag
	let tag = addBBCodeStartTag();

	// If an end tag was created, pair it with this start tag
	if (endTag)
	{
		tag.pairWith(endTag);
	}
}

/**
* Parse the BBCode suffix starting at current position
*
* Used to explicitly pair specific tags together, e.g.
*   [code:123][code]type your code here[/code][/code:123]
*/
function parseBBCodeSuffix()
{
	bbcodeSuffix = '';
	if (text[pos] === ':')
	{
		// Capture the colon and the (0 or more) digits following it
		bbcodeSuffix = /^:\d*/.exec(text.substring(pos))[0];

		// Move past the suffix
		pos += bbcodeSuffix.length;
	}
}

/**
* Parse a quoted attribute value that starts at current offset
*
* @return {string}
*/
function parseQuotedAttributeValue()
{
	let quote    = text[pos],
		valuePos = pos + 1,
		n;
	do
	{
		// Look for the next quote
		pos = text.indexOf(quote, pos + 1);
		if (pos < 0)
		{
			// No matching quote. Apparently that string never ends...
			throw '';
		}

		// Test for an odd number of backslashes before this character
		n = 1;
		while (text[pos - n] === '\\')
		{
			++n;
		}
	}
	while (n % 2 === 0);

	let attrValue = text.substring(valuePos, pos);
	if (attrValue.indexOf('\\') > -1)
	{
		attrValue = attrValue.replace(/\\([\\'"])/g, '$1');
	}

	// Skip past the closing quote
	++pos;

	return attrValue;
}