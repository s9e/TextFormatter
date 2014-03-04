/**#@+
* Boolean rules bitfield
*/
/** @const */ var RULE_AUTO_CLOSE        =   1;
/** @const */ var RULE_AUTO_REOPEN       =   2;
/** @const */ var RULE_BREAK_PARAGRAPH   =   4;
/** @const */ var RULE_CREATE_PARAGRAPHS =   8;
/** @const */ var RULE_IGNORE_TAGS       =  16;
/** @const */ var RULE_IGNORE_TEXT       =  32;
/** @const */ var RULE_IS_TRANSPARENT    =  64;
/** @const */ var RULE_NO_BR_CHILD       = 128;
/** @const */ var RULE_NO_BR_DESCENDANT  = 256;
/** @const */ var RULE_TRIM_WHITESPACE   = 512;
/**#@-*/

/**
* @const All the characters that are considered whitespace
*/
var WHITESPACE = " \n\t";

/**
* @type {!Logger} This parser's logger
*/
var logger = new Logger;

/**
* @type {!Object} Variables registered for use in filters
*/
var registeredVars;

/**
* @type {!Object} Tags' config
* @const
*/
var tagsConfig;

/**
* @type {!string} Text being parsed
*/
var text;

/**
* @type {!number} Length of the text being parsed
*/
var textLen;

/**
* @type {!number} Counter incremented everytime the parser is reset. Used to as a canary to detect
*                 whether the parser was reset during execution
*/
var uid = 0;

//==========================================================================
// Public API
//==========================================================================

/**
* Disable a tag
*
* @param {!string} tagName Name of the tag
*/
function disableTag(tagName)
{
	if (tagsConfig[tagName])
	{
		tagsConfig[tagName].isDisabled = true;
	}
}

/**
* Enable a tag
*
* @param {!string} tagName Name of the tag
*/
function enableTag(tagName)
{
	if (tagsConfig[tagName])
	{
		tagsConfig[tagName].isDisabled = false;
	}
}

/**
* Get this parser's Logger instance
*
* @return {!Logger}
*/
function getLogger()
{
	return logger;
}

/**
* Parse a text
*
* @param  {!string} _text Text to parse
* @return {!string}       XML representation
*/
function parse(_text)
{
	// Reset the parser and save the uid
	reset(_text);
	var _uid = uid;

	// Do the heavy lifting
	executePluginParsers();
	processTags();

	// Check the uid in case a plugin or a filter reset the parser mid-execution
	if (uid !== _uid)
	{
		throw 'The parser has been reset during execution';
	}

	return output;
}

/**
* Reset the parser for a new parsing
*
* @param {!string} _text Text to be parsed
*/
function reset(_text)
{
	// Normalize CR/CRLF to LF, remove control characters that aren't allowed in XML
	_text = _text.replace(/\r\n?/g, "\n", _text);
	_text = _text.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F]+/g, '', _text);

	// Clear the logs
	logger.clear();

	// Initialize the rest
	currentFixingCost = 0;
	isRich     = false;
	namespaces = {};
	output     = '';
	text       = _text;
	textLen    = text.length;
	tagStack   = [];
	tagStackIsSorted = true;
	wsPos      = 0;

	// Bump the UID
	++uid;
}

/**
* Change a tag's tagLimit
*
* NOTE: the default tagLimit should generally be set during configuration instead
*
* @param {!string} tagName  The tag's name, in UPPERCASE
* @param {!number} tagLimit
*/
function setTagLimit(tagName, tagLimit)
{
	if (tagsConfig[tagName])
	{
		tagsConfig[tagName].tagLimit = tagLimit;
	}
}

/**
* Change a tag's nestingLimit
*
* NOTE: the default nestingLimit should generally be set during configuration instead
*
* @param {!string} tagName      The tag's name, in UPPERCASE
* @param {!number} nestingLimit
*/
function setNestingLimit(tagName, nestingLimit)
{
	if (tagsConfig[tagName])
	{
		tagsConfig[tagName].nestingLimit = nestingLimit;
	}
}

//==========================================================================
// Filter processing
//==========================================================================

/**
* Execute all the attribute preprocessors of given tag
*
* @private
*
* @param  {!Tag}     tag       Source tag
* @param  {!Object}  tagConfig Tag's config
* @return {!boolean}           Unconditionally TRUE
*/
function executeAttributePreprocessors(tag, tagConfig)
{
	if (tagConfig.attributePreprocessors)
	{
		tagConfig.attributePreprocessors.forEach(function(attributePreprocessor)
		{
			var attrName = attributePreprocessor[0],
				regexp   = attributePreprocessor[1],
				map      = attributePreprocessor[2];

			if (!tag.hasAttribute(attrName))
			{
				return;
			}

			var m, attrValue = tag.getAttribute(attrName);

			// If the regexp matches, we remove the source attribute then we add the
			// captured attributes
			if (m = regexp.exec(attrValue))
			{
				// Set the target attributes
				map.forEach(function(targetName, mIndex)
				{
					// Skip captures with no targets and targets with no captures (in case of
					// optional captures)
					if (targetName === '' || typeof m[mIndex] !== 'string')
					{
						return;
					}

					var targetValue = m[mIndex];

					// Attribute preprocessors cannot overwrite other attributes but they can
					// overwrite themselves
					if (targetName === attrName || !tag.hasAttribute(targetName))
					{
						tag.setAttribute(targetName, targetValue);
					}
				});
			}
		});
	}

	return true;
}

/**
* Filter the attributes of given tag
*
* @private
*
* @param  {!Tag}     tag            Tag being checked
* @param  {!Object}  tagConfig      Tag's config
* @param  {!Object}  registeredVars Vars registered for use in attribute filters
* @param  {!Logger}  logger         This parser's Logger instance
* @return {!boolean}           Whether the whole attribute set is valid
*/
function filterAttributes(tag, tagConfig, registeredVars, logger)
{
	if (!tagConfig.attributes)
	{
		tag.setAttributes({});

		return true;
	}

	var attrName, attrConfig;

	// Generate values for attributes with a generator set
	if (HINT.attributeGenerator)
	{
		for (attrName in tagConfig.attributes)
		{
			attrConfig = tagConfig.attributes[attrName];

			if (attrConfig.generator)
			{
				tag.setAttribute(attrName, attrConfig.generator(attrName));
			}
		}
	}

	// Filter and remove invalid attributes
	var attributes = tag.getAttributes();
	for (attrName in attributes)
	{
		var attrValue = attributes[attrName];

		// Test whether this attribute exists and remove it if it doesn't
		if (!tagConfig.attributes[attrName])
		{
			tag.removeAttribute(attrName);
			continue;
		}

		attrConfig = tagConfig.attributes[attrName];

		// Test whether this attribute has a filterChain
		if (!attrConfig.filterChain)
		{
			continue;
		}

		// Record the name of the attribute being filtered into the logger
		logger.setAttribute(attrName);

		for (var i = 0; i < attrConfig.filterChain.length; ++i)
		{
			// NOTE: attrValue is intentionally set as the first argument to facilitate inlining
			attrValue = attrConfig.filterChain[i](attrValue, attrName);

			if (attrValue === false)
			{
				tag.removeAttribute(attrName);
				break;
			}
		}

		// Update the attribute value if it's valid
		if (attrValue !== false)
		{
			tag.setAttribute(attrName, attrValue);
		}

		// Remove the attribute's name from the logger
		logger.unsetAttribute();
	}

	// Iterate over the attribute definitions to handle missing attributes
	for (attrName in tagConfig.attributes)
	{
		attrConfig = tagConfig.attributes[attrName];

		// Test whether this attribute is missing
		if (!tag.hasAttribute(attrName))
		{
			if (HINT.attributeDefaultValue && attrConfig.defaultValue !== undefined)
			{
				// Use the attribute's default value
				tag.setAttribute(attrName, attrConfig.defaultValue);
			}
			else if (attrConfig.required)
			{
				// This attribute is missing, has no default value and is required, which means
				// the attribute set is invalid
				return false;
			}
		}
	}

	return true;
}

/**
* Execute given tag's filterChain
*
* @param  {!Tag}     tag Tag to filter
* @return {!boolean}     Whether the tag is valid
*/
function filterTag(tag)
{
	var tagName   = tag.getName(),
		tagConfig = tagsConfig[tagName],
		isValid   = true;

	if (tagConfig.filterChain)
	{
		// Record the tag being processed into the logger it can be added to the context of
		// messages logged during the execution
		logger.setTag(tag);

		for (var i = 0; i < tagConfig.filterChain.length; ++i)
		{
			if (!tagConfig.filterChain[i](tag, tagConfig))
			{
				isValid = false;
				break;
			}
		}

		// Remove the tag from the logger
		logger.unsetTag();
	}

	return isValid;
}