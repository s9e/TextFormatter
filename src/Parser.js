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
* @type {!boolean} Whether the output contains "rich" tags, IOW any tag that is not <p> or <br/>
*/
var isRich;

/**
* @type {!Logger} This parser's logger
*/
var logger = new Logger;

/**
* @type {!Object} Associative array of namespace prefixes in use in document (prefixes used as key)
*/
var namespaces;

/**
* @type {!string} This parser's output
*/
var output;

/**
* @type {!Object.<!Object>}
*/
var plugins;

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

/**
* @type {!number} Position before which we output text verbatim, without paragraphs or linebreaks
*/
var wsPos;

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

//==========================================================================
// Output handling
//==========================================================================

function htmlspecialchars_compat(str)
{
	var t = {
		'<' : '&lt;',
		'>' : '&gt;',
		'&' : '&amp;',
		'"' : '&quot;'
	}
	return str.replace(/[<>&"]/g, function(c) { return t[c]; });
}

function htmlspecialchars_noquotes(str)
{
	var t = {
		'<' : '&lt;',
		'>' : '&gt;',
		'&' : '&amp;'
	}
	return str.replace(/[<>&]/g, function(c) { return t[c]; });
}

/**
* Finalize the output by appending the rest of the unprocessed text and create the root node
*/
function finalizeOutput()
{
	var tmp;

	// Output the rest of the text and close the last paragraph
	outputText(textLen, 0, true);

	// Remove empty tag pairs, e.g. <I><U></U></I> as well as empty paragraphs
	do
	{
		tmp = output;
		output = output.replace(/<((?:\w+:)?\w+)[^>]*><\/\1>/g, '');
	}
	while (output !== tmp);

	// Merge consecutive <i> tags
	output = output.replace(/<\/i><i>/g, '', output);

	// Use a <r> root if the text is rich, or <t> for plain text (including <p></p> and <br/>)
	var tagName = (isRich) ? 'r' : 't';

	// Prepare the root node with all the namespace declarations
	tmp = '<' + tagName;
	for (var prefix in namespaces)
	{
		tmp += ' xmlns:' + prefix + '="urn:s9e:TextFormatter:' + prefix + '"';
	}

	output = tmp + '>' + output + '</' + tagName + '>';
}

/**
* Append a tag to the output
*
* @param {!Tag} tag Tag to append
*/
function outputTag(tag)
{
	isRich = true;

	var tagName    = tag.getName(),
		tagPos     = tag.getPos(),
		tagLen     = tag.getLen(),
		tagFlags   = tag.getFlags(),
		skipBefore = 0,
		skipAfter  = 0;

	if (HINT.RULE_TRIM_WHITESPACE && (tagFlags & RULE_TRIM_WHITESPACE))
	{
		skipBefore = (tag.isStartTag()) ? 2 : 1;
		skipAfter  = (tag.isEndTag())   ? 2 : 1;
	}

	// Current paragraph must end before the tag if:
	//  - the tag is a start (or self-closing) tag and it breaks paragraphs, or
	//  - the tag is an end tag (but not self-closing)
	var closeParagraph = false;
	if (tag.isStartTag())
	{
		if (HINT.RULE_BREAK_PARAGRAPH && (tagFlags & RULE_BREAK_PARAGRAPH))
		{
			closeParagraph = true;
		}
	}
	else
	{
		closeParagraph = true;
	}

	// Let the cursor catch up with this tag's position
	outputText(tagPos, skipBefore, closeParagraph);

	// Capture the text consumed by the tag
	var tagText = (tagLen)
				? htmlspecialchars_noquotes(text.substr(tagPos, tagLen))
				: '';

	// Output current tag
	if (tag.isStartTag())
	{
		// Handle paragraphs before opening the tag
		if (!HINT.RULE_BREAK_PARAGRAPH || !(tagFlags & RULE_BREAK_PARAGRAPH))
		{
			outputParagraphStart(tagPos);
		}

		// Record this tag's namespace, if applicable
		var colonPos = tagName.indexOf(':');
		if (colonPos > 0)
		{
			namespaces[tagName.substr(0, colonPos)] = 0;
		}

		// Open the start tag and add its attributes, but don't close the tag
		output += '<' + tagName;

		// We output the attributes in lexical order. Helps canonicalizing the output and could
		// prove useful someday
		var attributes = tag.getAttributes(),
			attributeNames = [];
		for (var attrName in attributes)
		{
			attributeNames.push([attrName]);
		}
		attributeNames.sort(
			function(a, b)
			{
				return (a > b) ? 1 : -1;
			}
		);
		attributeNames.forEach(
			function(attrName)
			{
				output += ' ' + attrName + '="' + htmlspecialchars_compat(attributes[attrName].toString()) + '"';
			}
		);

		if (tag.isSelfClosingTag())
		{
			if (tagLen)
			{
				output += '>' + tagText + '</' + tagName + '>';
			}
			else
			{
				output += '/>';
			}
		}
		else if (tagLen)
		{
			output += '><s>' + tagText + '</s>';
		}
		else
		{
			output += '>';
		}
	}
	else
	{
		if (tagLen)
		{
			output += '<e>' + tagText + '</e>';
		}

		output += '</' + tagName + '>';
	}

	// Move the cursor past the tag
	pos = tagPos + tagLen;

	// Skip newlines (no other whitespace) after this tag
	wsPos = pos;
	while (skipAfter && wsPos < textLen && text.charAt(wsPos) === "\n")
	{
		// Decrement the number of lines to skip
		--skipAfter;

		// Move the cursor past the newline
		++wsPos;
	}
}

/**
* Output the text between the cursor's position (included) and given position (not included)
*
* @param  {!number}  catchupPos     Position we're catching up to
* @param  {!number}  maxLines       Maximum number of lines to ignore at the end of the text
* @param  {!boolean} closeParagraph Whether to close the paragraph at the end, if applicable
*/
function outputText(catchupPos, maxLines, closeParagraph)
{
	if (closeParagraph)
	{
		if (!(context.flags & RULE_CREATE_PARAGRAPHS))
		{
			closeParagraph = false;
		}
		else
		{
			// Ignore any number of lines at the end if we're closing a paragraph
			maxLines = -1;
		}
	}

	if (pos >= catchupPos)
	{
		// We're already there, close the paragraph if applicable and return
		if (closeParagraph)
		{
			outputParagraphEnd();
		}
	}

	// Skip over previously identified whitespace if applicable
	if (wsPos > pos)
	{
		var skipPos = Math.min(catchupPos, wsPos);
		output += text.substr(pos, skipPos - pos);
		pos = skipPos;

		if (pos >= catchupPos)
		{
			// Skipped everything. Close the paragraph if applicable and return
			if (closeParagraph)
			{
				outputParagraphEnd();
			}
		}
	}

	var catchupLen, catchupText;

	// Test whether we're even supposed to output anything
	if (HINT.RULE_IGNORE_TEXT && context.flags & RULE_IGNORE_TEXT)
	{
		catchupLen  = catchupPos - pos,
		catchupText = text.substr(pos, catchupLen);

		// If the catchup text is not entirely composed of whitespace, we put it inside ignore tags
		if (!/^[ \n\t]*$/.test(catchupText))
		{
			catchupText = '<i>' + catchupText + '</i>';
		}

		output += catchupText;
		pos = catchupPos;

		if (closeParagraph)
		{
			outputParagraphEnd();
		}

		return;
	}

	// Compute the amount of text to ignore at the end of the output
	var ignorePos = catchupPos,
		ignoreLen = 0;

	// Ignore as many lines (including whitespace) as specified
	while (maxLines && --ignorePos >= pos)
	{
		var c = text.charAt(ignorePos);
		if (c !== ' ' && c !== "\n" && c !== "\t")
		{
			break;
		}

		if (c === "\n")
		{
			--maxLines;
		}

		++ignoreLen;
	}

	// Adjust catchupPos to ignore the text at the end
	catchupPos -= ignoreLen;

	// Break down the text in paragraphs if applicable
	if (HINT.RULE_CREATE_PARAGRAPHS && context.flags & RULE_CREATE_PARAGRAPHS)
	{
		if (!context.inParagraph)
		{
			outputWhitespace(catchupPos);

			if (catchupPos > pos)
			{
				outputParagraphStart(catchupPos);
			}
		}

		// Look for a paragraph break in this text
		var pbPos = text.indexOf("\n\n", pos);

		while (pbPos > -1 && pbPos < catchupPos)
		{
			outputText(pbPos, 0, true);
			outputParagraphStart(catchupPos);

			pbPos = text.indexOf("\n\n", pos);
		}
	}

	// Capture, escape and output the text
	if (catchupPos > pos)
	{
		catchupText = htmlspecialchars_noquotes(
			text.substr(pos, catchupPos - pos)
		);

		// Format line breaks if applicable
		if (!HINT.RULE_NO_BR_CHILD || !(context.flags & RULE_NO_BR_CHILD))
		{
			catchupText = catchupText.replace(/\n/g, "<br/>\n");
		}

		output += catchupText;
	}

	// Close the paragraph if applicable
	if (closeParagraph)
	{
		outputParagraphEnd();
	}

	// Add the ignored text if applicable
	if (ignoreLen)
	{
		output += text.substr(catchupPos, ignoreLen);
	}

	// Move the cursor past the text
	pos = catchupPos + ignoreLen;
}

/**
* Output a linebreak tag
*
* @param  {!Tag} tag
* @return void
*/
function outputBrTag(tag)
{
	outputText(tag.getPos(), 0, false);
	output += '<br/>';
}

/**
* Output an ignore tag
*
* @param  {!Tag} tag
* @return void
*/
function outputIgnoreTag(tag)
{
	var tagPos = tag.getPos(),
		tagLen = tag.getLen();

	// Capture the text to ignore
	var ignoreText = text.substr(tagPos, tagLen);

	// Catch up with the tag's position then output the tag
	outputText(tagPos, 0, false);
	output += '<i>' + htmlspecialchars_noquotes(ignoreText) + '</i>';
	isRich = true;

	// Move the cursor past this tag
	pos = tagPos + tagLen;
}

/**
* Start a paragraph between current position and given position, if applicable
*
* @param  {!number} maxPos Rightmost position at which the paragraph can be opened
*/
function outputParagraphStart(maxPos)
{
	if (!HINT.RULE_CREATE_PARAGRAPHS)
	{
		return;
	}

	// Do nothing if we're already in a paragraph, or if we don't use paragraphs
	if (context.inParagraph
	 || !(context.flags & RULE_CREATE_PARAGRAPHS))
	{
		return;
	}

	// Output the whitespace between pos and maxPos if applicable
	outputWhitespace(maxPos);

	// Open the paragraph, but only if it's not at the very end of the text
	if (pos < textLen)
	{
		output += '<p>';
		context.inParagraph = true;
	}
}

/**
* Close current paragraph at current position if applicable
*/
function outputParagraphEnd()
{
	// Do nothing if we're not in a paragraph
	if (!context.inParagraph)
	{
		return;
	}

	output += '</p>';
	context.inParagraph = false;
}

/**
* Skip as much whitespace after current position as possible
*
* @param  {!number} maxPos Rightmost character to be skipped
*/
function outputWhitespace(maxPos)
{
	while (pos < maxPos && " \n\t".indexOf(text.charAt(pos)) > -1)
	{
		output += text.charAt(pos);
		++pos;
	}
}

//==========================================================================
// Plugins handling
//==========================================================================

/**
* Disable a plugin
*
* @param {!string} pluginName Name of the plugin
*/
function disablePlugin(pluginName)
{
	if (plugins[pluginName])
	{
		plugins[pluginName].isDisabled = true;
	}
}

/**
* Enable a plugin
*
* @param {!string} pluginName Name of the plugin
*/
function enablePlugin(pluginName)
{
	if (plugins[pluginName])
	{
		plugins[pluginName].isDisabled = false;
	}
}

/**
* Get regexp matches in a manner similar to preg_match_all() with PREG_SET_ORDER | PREG_OFFSET_CAPTURE
*
* @param  {!RegExp} regexp
* @return {!Array.<!Array>}
*/
function getMatches(regexp)
{
	var matches = [], m;

	// Reset the regexp
	regexp.lastIndex = 0;

	while (m = regexp.exec(text))
	{
		// NOTE: coercing m.index to a number because Closure Compiler thinks pos is a string otherwise
		var pos   = +m['index'],
			match = [[m[0], pos]],
			i = 0;

		while (++i < m.length)
		{
			var str = m[i];

			// Sub-expressions that were not evaluated return undefined
			if (str === undefined)
			{
				match.push(['', -1]);
			}
			else
			{
				match.push([str, text.indexOf(str, pos)]);
				pos += str.length;
			}
		}

		matches.push(match);
	}

	return matches;
}

/**
* Execute all the plugins
*/
function executePluginParsers()
{
	for (var pluginName in plugins)
	{
		var plugin = plugins[pluginName];

		if (plugin.isDisabled)
		{
			continue;
		}

		if (plugin.quickMatch
		 && text.indexOf(plugin.quickMatch) < 0)
		{
			continue;
		}

		var matches = [];

		if (plugin.regexp)
		{
			matches = getMatches(plugin.regexp);

			var cnt = matches.length;

			if (!cnt)
			{
				continue;
			}

			if (cnt > plugin.regexpLimit)
			{
				if (HINT.regexpLimitActionAbort && plugin.regexpLimitAction === 'abort')
				{
					throw (pluginName + ' limit exceeded');
				}

				matches = matches.slice(0, plugin.regexpLimit);

				var msg = 'Regexp limit exceeded. Only the allowed number of matches will be processed',
					context = {
						'pluginName' : pluginName,
						'limit'      : plugin.regexpLimit
					};

				if (HINT.regexpLimitActionWarn && plugin.regexpLimitAction === 'warn')
				{
					logger.warn(msg, context);
				}
			}
		}

		// Execute the plugin's parser, which will add tags via addStartTag() and others
		plugin.parser(text, matches);
	}
}

/**
* Register a parser
*
* Can be used to add a new parser with no plugin config, or pre-generate a parser for an
* existing plugin
*
* @param  {!string}   pluginName
* @param  {!Function} parser
*/
function registerParser(pluginName, parser)
{
	// Create an empty config for this plugin to ensure it is executed
	if (!plugins[pluginName])
	{
		plugins[pluginName] = {};
	}

	plugins[pluginName].parser = parser;
}

//==========================================================================
// Rules handling
//==========================================================================

/**
* Apply closeAncestor rules associated with given tag
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether a new tag has been added
*/
function closeAncestor(tag)
{
	if (!HINT.closeAncestor)
	{
		return false;
	}

	if (openTags.length)
	{
		var tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.closeAncestor)
		{
			var i = openTags.length;

			while (--i >= 0)
			{
				var ancestor     = openTags[i],
					ancestorName = ancestor.getName();

				if (tagConfig.rules.closeAncestor[ancestorName])
				{
					// We have to close this ancestor. First we reinsert this tag...
					tagStack.push(tag);

					// ...then we add a new end tag for it
					addMagicEndTag(ancestor, tag.getPos());

					return true;
				}
			}
		}
	}

	return false;
}

/**
* Apply closeParent rules associated with given tag
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether a new tag has been added
*/
function closeParent(tag)
{
	if (!HINT.closeParent)
	{
		return false;
	}

	if (openTags.length)
	{
		var tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.closeParent)
		{
			var parent     = openTags[openTags.length - 1],
				parentName = parent.getName();

			if (tagConfig.rules.closeParent[parentName])
			{
				// We have to close that parent. First we reinsert the tag...
				tagStack.push(tag);

				// ...then we add a new end tag for it
				addMagicEndTag(parent, tag.getPos());

				return true;
			}
		}
	}

	return false;
}

/**
* Apply fosterParent rules associated with given tag
*
* NOTE: this rule has the potential for creating an unbounded loop, either if a tag tries to
*       foster itself or two or more tags try to foster each other in a loop. We mitigate the
*       risk by preventing a tag from creating a child of itself (the parent still gets closed)
*       and by checking and increasing the currentFixingCost so that a loop of multiple tags
*       do not run indefinitely. The default tagLimit and nestingLimit also serve to prevent the
*       loop from running indefinitely
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether a new tag has been added
*/
function fosterParent(tag)
{
	if (!HINT.fosterParent)
	{
		return false;
	}

	if (openTags.length)
	{
		var tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.fosterParent)
		{
			var parent     = openTags[openTags.length - 1],
				parentName = parent.getName();

			if (tagConfig.rules.fosterParent[parentName])
			{
				if (parentName !== tagName && currentFixingCost < maxFixingCost)
				{
					// Add a 0-width copy of the parent tag right after this tag, and make it
					// depend on this tag
					var child = addCopyTag(parent, tag.getPos() + tag.getLen(), 0);
					tag.cascadeInvalidationTo(child);
				}

				++currentFixingCost;

				// Reinsert current tag
				tagStack.push(tag);

				// And finally close its parent
				addMagicEndTag(parent, tag.getPos());

				return true;
			}
		}
	}

	return false;
}

/**
* Apply requireAncestor rules associated with given tag
*
* @param  {!Tag}     tag Tag
* @return {!boolean}     Whether this tag has an unfulfilled requireAncestor requirement
*/
function requireAncestor(tag)
{
	if (!HINT.requireAncestor)
	{
		return false;
	}

	var tagName   = tag.getName(),
		tagConfig = tagsConfig[tagName];

	if (tagConfig.rules.requireAncestor)
	{
		var i = tagConfig.rules.requireAncestor.length;
		while (--i >= 0)
		{
			var ancestorName = tagConfig.rules.requireAncestor[i];
			if (cntOpen[ancestorName])
			{
				return false;
			}
		}

		logger.err('Tag requires an ancestor', {
			'requireAncestor' : tagConfig.rules.requireAncestor.join(', '),
			'tag'             : tag
		});

		return true;
	}

	return false;
}