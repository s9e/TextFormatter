/**#@+
* Boolean rules bitfield
*/
const RULE_AUTO_CLOSE        = 1 << 0;
const RULE_AUTO_REOPEN       = 1 << 1;
const RULE_BREAK_PARAGRAPH   = 1 << 2;
const RULE_CREATE_PARAGRAPHS = 1 << 3;
const RULE_DISABLE_AUTO_BR   = 1 << 4;
const RULE_ENABLE_AUTO_BR    = 1 << 5;
const RULE_IGNORE_TAGS       = 1 << 6;
const RULE_IGNORE_TEXT       = 1 << 7;
const RULE_IGNORE_WHITESPACE = 1 << 8;
const RULE_IS_TRANSPARENT    = 1 << 9;
const RULE_PREVENT_BR        = 1 << 10;
const RULE_SUSPEND_AUTO_BR   = 1 << 11;
const RULE_TRIM_FIRST_LINE   = 1 << 12;
/**#@-*/

/**
* @const Bitwise disjunction of rules related to automatic line breaks
*/
const RULES_AUTO_LINEBREAKS = RULE_DISABLE_AUTO_BR | RULE_ENABLE_AUTO_BR | RULE_SUSPEND_AUTO_BR;

/**
* @const Bitwise disjunction of rules that are inherited by subcontexts
*/
const RULES_INHERITANCE = RULE_ENABLE_AUTO_BR;

/**
* @const All the characters that are considered whitespace
*/
const WHITESPACE = " \n\t";

/**
* @type {!Object.<string,number>} Number of open tags for each tag name
*/
let cntOpen;

/**
* @type {!Object.<string,number>} Number of times each tag has been used
*/
let cntTotal;

/**
* @type {!Object} Current context
*/
let context;

/**
* @type {number} How hard the parser has worked on fixing bad markup so far
*/
let currentFixingCost;

/**
* @type {?Tag} Current tag being processed
*/
let currentTag;

/**
* @type {boolean} Whether the output contains "rich" tags, IOW any tag that is not <p> or <br/>
*/
let isRich;

/**
* @type {!Logger} This parser's logger
*/
let logger = new Logger;

/**
* @type {number} How hard the parser should work on fixing bad markup
*/
let maxFixingCost = 10000;

/**
* @type {!Object} Associative array of namespace prefixes in use in document (prefixes used as key)
*/
let namespaces;

/**
* @type {!Array.<!Tag>} Stack of open tags (instances of Tag)
*/
let openTags;

/**
* @type {string} This parser's output
*/
let output;

/**
* @type {!Object.<!Object>}
*/
const plugins;

/**
* @type {number} Position of the cursor in the original text
*/
let pos;

/**
* @type {!Object} Variables registered for use in filters
*/
const registeredVars;

/**
* @type {!Object} Root context, used at the root of the document
*/
const rootContext;

/**
* @type {!Object} Tags' config
*/
const tagsConfig;

/**
* @type {!Array.<!Tag>} Tag storage
*/
let tagStack;

/**
* @type {boolean} Whether the tags in the stack are sorted
*/
let tagStackIsSorted;

/**
* @type {string} Text being parsed
*/
let text;

/**
* @type {number} Length of the text being parsed
*/
let textLen;

/**
* @type {number} Counter incremented everytime the parser is reset. Used to as a canary to detect
*                 whether the parser was reset during execution
*/
let uid = 0;

/**
* @type {number} Position before which we output text verbatim, without paragraphs or linebreaks
*/
let wsPos;

//==========================================================================
// Public API
//==========================================================================

/**
* Disable a tag
*
* @param {string} tagName Name of the tag
*/
function disableTag(tagName)
{
	if (tagsConfig[tagName])
	{
		copyTagConfig(tagName).isDisabled = true;
	}
}

/**
* Enable a tag
*
* @param {string} tagName Name of the tag
*/
function enableTag(tagName)
{
	if (tagsConfig[tagName])
	{
		copyTagConfig(tagName).isDisabled = false;
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
* @param  {string} _text Text to parse
* @return {string}       XML representation
*/
function parse(_text)
{
	// Reset the parser and save the uid
	reset(_text);
	let _uid = uid;

	// Do the heavy lifting
	executePluginParsers();
	processTags();

	// Finalize the document
	finalizeOutput();

	// Check the uid in case a plugin or a filter reset the parser mid-execution
	if (uid !== _uid)
	{
		throw 'The parser has been reset during execution';
	}

	// Log a warning if the fixing cost limit was exceeded
	if (currentFixingCost > maxFixingCost)
	{
		logger.warn('Fixing cost limit exceeded');
	}

	return output;
}

/**
* Reset the parser for a new parsing
*
* @param {string} _text Text to be parsed
*/
function reset(_text)
{
	// Normalize CR/CRLF to LF, remove characters that aren't allowed in XML
	_text = _text.replace(/\r\n?/g, "\n");
	_text = _text.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\uFFFE\uFFFF]/g, '');

	// Clear the logs
	logger.clear();

	// Initialize the rest
	cntOpen           = {};
	cntTotal          = {};
	currentFixingCost = 0;
	currentTag        = null;
	isRich            = false;
	namespaces        = {};
	openTags          = [];
	output            = '';
	pos               = 0;
	tagStack          = [];
	tagStackIsSorted  = false;
	text              = _text;
	textLen           = text.length;
	wsPos             = 0;

	// Initialize the root context
	context = rootContext;
	context.inParagraph = false;

	// Bump the UID
	++uid;
}

/**
* Change a tag's tagLimit
*
* NOTE: the default tagLimit should generally be set during configuration instead
*
* @param {string} tagName  The tag's name, in UPPERCASE
* @param {number} tagLimit
*/
function setTagLimit(tagName, tagLimit)
{
	if (tagsConfig[tagName])
	{
		copyTagConfig(tagName).tagLimit = tagLimit;
	}
}

/**
* Change a tag's nestingLimit
*
* NOTE: the default nestingLimit should generally be set during configuration instead
*
* @param {string} tagName      The tag's name, in UPPERCASE
* @param {number} nestingLimit
*/
function setNestingLimit(tagName, nestingLimit)
{
	if (tagsConfig[tagName])
	{
		copyTagConfig(tagName).nestingLimit = nestingLimit;
	}
}

/**
* Copy a tag's config
*
* This method ensures that the tag's config is its own object and not shared with another
* identical tag
*
* @param  {string} tagName Tag's name
* @return {!Object}         Tag's config
*/
function copyTagConfig(tagName)
{
	let tagConfig = {}, k;
	for (k in tagsConfig[tagName])
	{
		tagConfig[k] = tagsConfig[tagName][k];
	}

	return tagsConfig[tagName] = tagConfig;
}

//==========================================================================
// Output handling
//==========================================================================

/**
* Replace Unicode characters outside the BMP with XML entities in the output
*/
function encodeUnicodeSupplementaryCharacters()
{
	output = output.replace(
		/[\uD800-\uDBFF][\uDC00-\uDFFF]/g,
		encodeUnicodeSupplementaryCharactersCallback
	);
}

/**
* Encode given surrogate pair into an XML entity
*
* @param  {string} pair Surrogate pair
* @return {string}      XML entity
*/
function encodeUnicodeSupplementaryCharactersCallback(pair)
{
	let cp = (pair.charCodeAt(0) << 10) + pair.charCodeAt(1) - 56613888;

	return '&#' + cp + ';';
}

/**
* Finalize the output by appending the rest of the unprocessed text and create the root node
*/
function finalizeOutput()
{
	let tmp;

	// Output the rest of the text and close the last paragraph
	outputText(textLen, 0, true);

	// Remove empty tag pairs, e.g. <I><U></U></I> as well as empty paragraphs
	do
	{
		tmp = output;
		output = output.replace(/<([^ />]+)[^>]*><\/\1>/g, '');
	}
	while (output !== tmp);

	// Merge consecutive <i> tags
	output = output.replace(/<\/i><i>/g, '');

	// Remove illegal characters from the output to ensure it's valid XML
	output = output.replace(/[\x00-\x08\x0B-\x1F\uFFFE\uFFFF]/g, '');

	// Encode Unicode characters that are outside of the BMP
	encodeUnicodeSupplementaryCharacters();

	// Use a <r> root if the text is rich, or <t> for plain text (including <p></p> and <br/>)
	let tagName = (isRich) ? 'r' : 't';

	// Prepare the root node with all the namespace declarations
	tmp = '<' + tagName;
	if (HINT.namespaces)
	{
		for (let prefix in namespaces)
		{
			tmp += ' xmlns:' + prefix + '="urn:s9e:TextFormatter:' + prefix + '"';
		}
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

	let tagName    = tag.getName(),
		tagPos     = tag.getPos(),
		tagLen     = tag.getLen(),
		tagFlags   = tag.getFlags(),
		skipBefore = 0,
		skipAfter  = 0;

	if (HINT.RULE_IGNORE_WHITESPACE && (tagFlags & RULE_IGNORE_WHITESPACE))
	{
		skipBefore = 1;
		skipAfter  = (tag.isEndTag()) ? 2 : 1;
	}

	// Current paragraph must end before the tag if:
	//  - the tag is a start (or self-closing) tag and it breaks paragraphs, or
	//  - the tag is an end tag (but not self-closing)
	let closeParagraph = !!(!tag.isStartTag() || (HINT.RULE_BREAK_PARAGRAPH && (tagFlags & RULE_BREAK_PARAGRAPH)));

	// Let the cursor catch up with this tag's position
	outputText(tagPos, skipBefore, closeParagraph);

	// Capture the text consumed by the tag
	let tagText = (tagLen)
				? htmlspecialchars_noquotes(text.substring(tagPos, tagPos + tagLen))
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
		if (HINT.namespaces)
		{
			let colonPos = tagName.indexOf(':');
			if (colonPos > 0)
			{
				namespaces[tagName.substring(0, colonPos)] = 0;
			}
		}

		// Open the start tag and add its attributes, but don't close the tag
		output += '<' + tagName;

		// We output the attributes in lexical order. Helps canonicalizing the output and could
		// prove useful someday
		let attributes = tag.getAttributes(),
			attributeNames = [];
		for (let attrName in attributes)
		{
			attributeNames.push(attrName);
		}
		attributeNames.sort((a, b) => (a > b) ? 1 : -1);
		attributeNames.forEach(
			(attrName) =>
			{
				output += ' ' + attrName + '="' + htmlspecialchars_compat(attributes[attrName].toString()).replace(/\n/g, '&#10;') + '"';
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
	while (skipAfter && wsPos < textLen && text[wsPos] === "\n")
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
* @param  {number}  catchupPos     Position we're catching up to
* @param  {number}  maxLines       Maximum number of lines to ignore at the end of the text
* @param  {boolean} closeParagraph Whether to close the paragraph at the end, if applicable
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
		let skipPos = Math.min(catchupPos, wsPos);
		output += text.substring(pos, skipPos);
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

	let catchupText;

	// Test whether we're even supposed to output anything
	if (HINT.RULE_IGNORE_TEXT && context.flags & RULE_IGNORE_TEXT)
	{
		catchupText = text.substring(pos, catchupPos);

		// If the catchup text is not entirely composed of whitespace, we put it inside ignore tags
		if (!/^[ \n\t]*$/.test(catchupText))
		{
			catchupText = '<i>' + htmlspecialchars_noquotes(catchupText) + '</i>';
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
	let ignorePos = catchupPos,
		ignoreLen = 0;

	// Ignore as many lines (including whitespace) as specified
	while (maxLines && --ignorePos >= pos)
	{
		let c = text[ignorePos];
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
		let pbPos = text.indexOf("\n\n", pos);

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
			text.substring(pos, catchupPos)
		);

		// Format line breaks if applicable
		if (HINT.RULE_ENABLE_AUTO_BR && (context.flags & RULES_AUTO_LINEBREAKS) === RULE_ENABLE_AUTO_BR)
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
		output += text.substring(catchupPos, catchupPos + ignoreLen);
	}

	// Move the cursor past the text
	pos = catchupPos + ignoreLen;
}

/**
* Output a linebreak tag
*
* @param {!Tag} tag
*/
function outputBrTag(tag)
{
	outputText(tag.getPos(), 0, false);
	output += '<br/>';
}

/**
* Output an ignore tag
*
* @param {!Tag} tag
*/
function outputIgnoreTag(tag)
{
	let tagPos = tag.getPos(),
		tagLen = tag.getLen();

	// Capture the text to ignore
	let ignoreText = text.substring(tagPos, tagPos + tagLen);

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
* @param  {number} maxPos Rightmost position at which the paragraph can be opened
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
* Output the content of a verbatim tag
*
* @param {!Tag} tag
*/
function outputVerbatim(tag)
{
	let flags = context.flags;
	context.flags = tag.getFlags();
	outputText(currentTag.getPos() + currentTag.getLen(), 0, false);
	context.flags = flags;
}

/**
* Skip as much whitespace after current position as possible
*
* @param  {number} maxPos Rightmost character to be skipped
*/
function outputWhitespace(maxPos)
{
	while (pos < maxPos && " \n\t".indexOf(text[pos]) > -1)
	{
		output += text[pos];
		++pos;
	}
}

//==========================================================================
// Plugins handling
//==========================================================================

/**
* Disable a plugin
*
* @param {string} pluginName Name of the plugin
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
* @param {string} pluginName Name of the plugin
*/
function enablePlugin(pluginName)
{
	if (plugins[pluginName])
	{
		plugins[pluginName].isDisabled = false;
	}
}

/**
* Execute given plugin
*
* @param {string} pluginName Plugin's name
*/
function executePluginParser(pluginName)
{
	let pluginConfig = plugins[pluginName];
	if (pluginConfig.quickMatch && text.indexOf(pluginConfig.quickMatch) < 0)
	{
		return;
	}

	let matches = [];
	if (HINT.regexp && HINT.regexpLimit && typeof pluginConfig.regexp !== 'undefined' && typeof pluginConfig.regexpLimit !== 'undefined')
	{
		matches = getMatches(pluginConfig.regexp, pluginConfig.regexpLimit);
		if (!matches.length)
		{
			return;
		}
	}

	// Execute the plugin's parser, which will add tags via addStartTag() and others
	getPluginParser(pluginName)(text, matches);
}

/**
* Execute all the plugins
*/
function executePluginParsers()
{
	for (let pluginName in plugins)
	{
		if (!plugins[pluginName].isDisabled)
		{
			executePluginParser(pluginName);
		}
	}
}

/**
* Get regexp matches in a manner similar to preg_match_all() with PREG_SET_ORDER | PREG_OFFSET_CAPTURE
*
* @param  {!RegExp} regexp
* @param  {number}  limit
* @return {!Array.<!Array>}
*/
function getMatches(regexp, limit)
{
	// Reset the regexp
	regexp.lastIndex = 0;
	let matches = [], cnt = 0, m;
	while (++cnt <= limit && (m = regexp.exec(text)))
	{
		// NOTE: coercing m.index to a number because Closure Compiler thinks pos is a string otherwise
		let pos   = m.index,
			match = [[m[0], pos]],
			i = 0;
		while (++i < m.length)
		{
			let str = m[i];

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
* Get the callback for given plugin's parser
*
* @param  {string} pluginName
* @return {function(string, !Array)}
*/
function getPluginParser(pluginName)
{
	return plugins[pluginName].parser;
}

/**
* Register a parser
*
* Can be used to add a new parser with no plugin config, or pre-generate a parser for an
* existing plugin
*
* @param  {string}    pluginName
* @param  {!Function} parser
* @param  {?RegExp=}  regexp
* @param  {number=}   limit
*/
function registerParser(pluginName, parser, regexp, limit)
{
	// Create an empty config for this plugin to ensure it is executed
	if (!plugins[pluginName])
	{
		plugins[pluginName] = {};
	}
	if (regexp)
	{
		plugins[pluginName].regexp = regexp;
		plugins[pluginName].limit  = limit || Infinity;
	}
	plugins[pluginName].parser = parser;
}

//==========================================================================
// Rules handling
//==========================================================================

/**
* Apply closeAncestor rules associated with given tag
*
* @param  {!Tag}    tag Tag
* @return {boolean}     Whether a new tag has been added
*/
function closeAncestor(tag)
{
	if (!HINT.closeAncestor)
	{
		return false;
	}

	if (openTags.length)
	{
		let tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.closeAncestor)
		{
			let i = openTags.length;

			while (--i >= 0)
			{
				let ancestor     = openTags[i],
					ancestorName = ancestor.getName();

				if (tagConfig.rules.closeAncestor[ancestorName])
				{
					++currentFixingCost;

					// We have to close this ancestor. First we reinsert this tag...
					tagStack.push(tag);

					// ...then we add a new end tag for it with a better priority
					addMagicEndTag(ancestor, tag.getPos(), tag.getSortPriority() - 1);

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
* @param  {!Tag}    tag Tag
* @return {boolean}     Whether a new tag has been added
*/
function closeParent(tag)
{
	if (!HINT.closeParent)
	{
		return false;
	}

	if (openTags.length)
	{
		let tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.closeParent)
		{
			let parent     = openTags[openTags.length - 1],
				parentName = parent.getName();

			if (tagConfig.rules.closeParent[parentName])
			{
				++currentFixingCost;

				// We have to close that parent. First we reinsert the tag...
				tagStack.push(tag);

				// ...then we add a new end tag for it with a better priority
				addMagicEndTag(parent, tag.getPos(), tag.getSortPriority() - 1);

				return true;
			}
		}
	}

	return false;
}

/**
* Apply the createChild rules associated with given tag
*
* @param {!Tag} tag Tag
*/
function createChild(tag)
{
	if (!HINT.createChild)
	{
		return;
	}

	let tagConfig = tagsConfig[tag.getName()];
	if (tagConfig.rules.createChild)
	{
		let priority = -1000,
			_text    = text.substring(pos),
			tagPos   = pos + _text.length - _text.replace(/^[ \n\r\t]+/, '').length;
		tagConfig.rules.createChild.forEach((tagName) =>
		{
			addStartTag(tagName, tagPos, 0, ++priority);
		});
	}
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
* @param  {!Tag}    tag Tag
* @return {boolean}     Whether a new tag has been added
*/
function fosterParent(tag)
{
	if (!HINT.fosterParent)
	{
		return false;
	}

	if (openTags.length)
	{
		let tagName   = tag.getName(),
			tagConfig = tagsConfig[tagName];

		if (tagConfig.rules.fosterParent)
		{
			let parent     = openTags[openTags.length - 1],
				parentName = parent.getName();

			if (tagConfig.rules.fosterParent[parentName])
			{
				if (parentName !== tagName && currentFixingCost < maxFixingCost)
				{
					addFosterTag(tag, parent);
				}

				// Reinsert current tag
				tagStack.push(tag);

				// And finally close its parent with a priority that ensures it is processed
				// before this tag
				addMagicEndTag(parent, tag.getPos(), tag.getSortPriority() - 1);

				// Adjust the fixing cost to account for the additional tags/processing
				currentFixingCost += 4;

				return true;
			}
		}
	}

	return false;
}

/**
* Apply requireAncestor rules associated with given tag
*
* @param  {!Tag}    tag Tag
* @return {boolean}     Whether this tag has an unfulfilled requireAncestor requirement
*/
function requireAncestor(tag)
{
	if (!HINT.requireAncestor)
	{
		return false;
	}

	let tagName   = tag.getName(),
		tagConfig = tagsConfig[tagName];

	if (tagConfig.rules.requireAncestor)
	{
		let i = tagConfig.rules.requireAncestor.length;
		while (--i >= 0)
		{
			let ancestorName = tagConfig.rules.requireAncestor[i];
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

//==========================================================================
// Tag processing
//==========================================================================

/**
* Create and add a copy of a tag as a child of a given tag
*
* @param {!Tag} tag       Current tag
* @param {!Tag} fosterTag Tag to foster
*/
function addFosterTag(tag, fosterTag)
{
	let coords    = getMagicStartCoords(tag.getPos() + tag.getLen()),
		childPos  = coords[0],
		childPrio = coords[1];

	// Add a 0-width copy of the parent tag after this tag and make it depend on this tag
	let childTag = addCopyTag(fosterTag, childPos, 0, childPrio);
	tag.cascadeInvalidationTo(childTag);
}

/**
* Create and add an end tag for given start tag at given position
*
* @param  {!Tag}    startTag Start tag
* @param  {number}  tagPos   End tag's position (will be adjusted for whitespace if applicable)
* @param  {number=} prio     End tag's priority
* @return {!Tag}
*/
function addMagicEndTag(startTag, tagPos, prio)
{
	let tagName = startTag.getName();

	// Adjust the end tag's position if whitespace is to be minimized
	if (HINT.RULE_IGNORE_WHITESPACE && ((currentTag.getFlags() | startTag.getFlags()) & RULE_IGNORE_WHITESPACE))
	{
		tagPos = getMagicEndPos(tagPos);
	}

	// Add a 0-width end tag that is paired with the given start tag
	let endTag = addEndTag(tagName, tagPos, 0, prio || 0);
	endTag.pairWith(startTag);

	return endTag;
}

/**
* Compute the position of a magic end tag, adjusted for whitespace
*
* @param  {number} tagPos Rightmost possible position for the tag
* @return {number}
*/
function getMagicEndPos(tagPos)
{
	// Back up from given position to the cursor's position until we find a character that
	// is not whitespace
	while (tagPos > pos && WHITESPACE.indexOf(text[tagPos - 1]) > -1)
	{
		--tagPos;
	}

	return tagPos;
}

/**
* Compute the position and priority of a magic start tag, adjusted for whitespace
*
* @param  {number} tagPos Leftmost possible position for the tag
* @return {!Array}        [Tag pos, priority]
*/
function getMagicStartCoords(tagPos)
{
	let nextPos, nextPrio, nextTag, prio;
	if (!tagStack.length)
	{
		// Set the next position outside the text boundaries
		nextPos  = textLen + 1;
		nextPrio = 0;
	}
	else
	{
		nextTag  = tagStack[tagStack.length - 1];
		nextPos  = nextTag.getPos();
		nextPrio = nextTag.getSortPriority();
	}

	// Find the first non-whitespace position before next tag or the end of text
	while (tagPos < nextPos && WHITESPACE.indexOf(text[tagPos]) > -1)
	{
		++tagPos;
	}

	// Set a priority that ensures this tag appears before the next tag
	prio = (tagPos === nextPos) ? nextPrio - 1 : 0;

	return [tagPos, prio];
}

/**
* Test whether given start tag is immediately followed by a closing tag
*
* @param  {!Tag} tag Start tag (including self-closing)
* @return {boolean}
*/
function isFollowedByClosingTag(tag)
{
	return (!tagStack.length) ? false : tagStack[tagStack.length - 1].canClose(tag);
}

/**
* Process all tags in the stack
*/
function processTags()
{
	if (!tagStack.length)
	{
		return;
	}

	// Initialize the count tables
	for (let tagName in tagsConfig)
	{
		cntOpen[tagName]  = 0;
		cntTotal[tagName] = 0;
	}

	// Process the tag stack, close tags that were left open and repeat until done
	do
	{
		while (tagStack.length)
		{
			if (!tagStackIsSorted)
			{
				sortTags();
			}

			currentTag = tagStack.pop();
			processCurrentTag();
		}

		// Close tags that were left open
		openTags.forEach((startTag) =>
		{
			// NOTE: we add tags in hierarchical order (ancestors to descendants) but since
			//       the stack is processed in LIFO order, it means that tags get closed in
			//       the correct order, from descendants to ancestors
			addMagicEndTag(startTag, textLen);
		});
	}
	while (tagStack.length);
}

/**
* Process current tag
*/
function processCurrentTag()
{
	// Invalidate current tag if tags are disabled and current tag would not close the last open
	// tag and is not a system tag
	if ((context.flags & RULE_IGNORE_TAGS)
	 && !currentTag.canClose(openTags[openTags.length - 1])
	 && !currentTag.isSystemTag())
	{
		currentTag.invalidate();
	}

	let tagPos = currentTag.getPos(),
		tagLen = currentTag.getLen();

	// Test whether the cursor passed this tag's position already
	if (pos > tagPos && !currentTag.isInvalid())
	{
		// Test whether this tag is paired with a start tag and this tag is still open
		let startTag = currentTag.getStartTag();

		if (startTag && openTags.indexOf(startTag) >= 0)
		{
			// Create an end tag that matches current tag's start tag, which consumes as much of
			// the same text as current tag and is paired with the same start tag
			addEndTag(
				startTag.getName(),
				pos,
				Math.max(0, tagPos + tagLen - pos)
			).pairWith(startTag);

			// Note that current tag is not invalidated, it's merely replaced
			return;
		}

		// If this is an ignore tag, try to ignore as much as the remaining text as possible
		if (currentTag.isIgnoreTag())
		{
			let ignoreLen = tagPos + tagLen - pos;

			if (ignoreLen > 0)
			{
				// Create a new ignore tag and move on
				addIgnoreTag(pos, ignoreLen);

				return;
			}
		}

		// Skipped tags are invalidated
		currentTag.invalidate();
	}

	if (currentTag.isInvalid())
	{
		return;
	}

	if (currentTag.isIgnoreTag())
	{
		outputIgnoreTag(currentTag);
	}
	else if (currentTag.isBrTag())
	{
		// Output the tag if it's allowed, ignore it otherwise
		if (!HINT.RULE_PREVENT_BR || !(context.flags & RULE_PREVENT_BR))
		{
			outputBrTag(currentTag);
		}
	}
	else if (currentTag.isParagraphBreak())
	{
		outputText(currentTag.getPos(), 0, true);
	}
	else if (currentTag.isVerbatim())
	{
		outputVerbatim(currentTag);
	}
	else if (currentTag.isStartTag())
	{
		processStartTag(currentTag);
	}
	else
	{
		processEndTag(currentTag);
	}
}

/**
* Process given start tag (including self-closing tags) at current position
*
* @param {!Tag} tag Start tag (including self-closing)
*/
function processStartTag(tag)
{
	let tagName   = tag.getName(),
		tagConfig = tagsConfig[tagName];

	// 1. Check that this tag has not reached its global limit tagLimit
	// 2. Execute this tag's filterChain, which will filter/validate its attributes
	// 3. Apply closeParent, closeAncestor and fosterParent rules
	// 4. Check for nestingLimit
	// 5. Apply requireAncestor rules
	//
	// This order ensures that the tag is valid and within the set limits before we attempt to
	// close parents or ancestors. We need to close ancestors before we can check for nesting
	// limits, whether this tag is allowed within current context (the context may change
	// as ancestors are closed) or whether the required ancestors are still there (they might
	// have been closed by a rule.)
	if (cntTotal[tagName] >= tagConfig.tagLimit)
	{
		logger.err(
			'Tag limit exceeded',
			{
				'tag'      : tag,
				'tagName'  : tagName,
				'tagLimit' : tagConfig.tagLimit
			}
		);
		tag.invalidate();

		return;
	}

	filterTag(tag);
	if (tag.isInvalid())
	{
		return;
	}

	if (currentFixingCost < maxFixingCost)
	{
		if (fosterParent(tag) || closeParent(tag) || closeAncestor(tag))
		{
			// This tag parent/ancestor needs to be closed, we just return (the tag is still valid)
			return;
		}
	}

	if (cntOpen[tagName] >= tagConfig.nestingLimit)
	{
		logger.err(
			'Nesting limit exceeded',
			{
				'tag'          : tag,
				'tagName'      : tagName,
				'nestingLimit' : tagConfig.nestingLimit
			}
		);
		tag.invalidate();

		return;
	}

	if (!tagIsAllowed(tagName))
	{
		let msg     = 'Tag is not allowed in this context',
			context = {'tag': tag, 'tagName': tagName};
		if (tag.getLen() > 0)
		{
			logger.warn(msg, context);
		}
		else
		{
			logger.debug(msg, context);
		}
		tag.invalidate();

		return;
	}

	if (requireAncestor(tag))
	{
		tag.invalidate();

		return;
	}

	// If this tag has an autoClose rule and it's not self-closed, paired with an end tag, or
	// immediately followed by an end tag, we replace it with a self-closing tag with the same
	// properties
	if (HINT.RULE_AUTO_CLOSE
	 && tag.getFlags() & RULE_AUTO_CLOSE
	 && !tag.isSelfClosingTag()
	 && !tag.getEndTag()
	 && !isFollowedByClosingTag(tag))
	{
		let newTag = new Tag(Tag.SELF_CLOSING_TAG, tagName, tag.getPos(), tag.getLen());
		newTag.setAttributes(tag.getAttributes());
		newTag.setFlags(tag.getFlags());

		tag = newTag;
	}

	if (HINT.RULE_TRIM_FIRST_LINE
	 && tag.getFlags() & RULE_TRIM_FIRST_LINE
	 && text[tag.getPos() + tag.getLen()] === "\n")
	{
		addIgnoreTag(tag.getPos() + tag.getLen(), 1);
	}

	// This tag is valid, output it and update the context
	outputTag(tag);
	pushContext(tag);

	// Apply the createChild rules if applicable
	createChild(tag);
}

/**
* Process given end tag at current position
*
* @param {!Tag} tag End tag
*/
function processEndTag(tag)
{
	let tagName = tag.getName();

	if (!cntOpen[tagName])
	{
		// This is an end tag with no start tag
		return;
	}

	/**
	* @type {!Array.<!Tag>} List of tags need to be closed before given tag
	*/
	let closeTags = [];

	// Iterate through all open tags from last to first to find a match for our tag
	let i = openTags.length;
	while (--i >= 0)
	{
		let openTag = openTags[i];

		if (tag.canClose(openTag))
		{
			break;
		}

		closeTags.push(openTag);
		++currentFixingCost;
	}

	if (i < 0)
	{
		// Did not find a matching tag
		logger.debug('Skipping end tag with no start tag', {'tag': tag});

		return;
	}

	// Accumulate flags to determine whether whitespace should be trimmed
	let flags = tag.getFlags();
	closeTags.forEach((openTag) =>
	{
		flags |= openTag.getFlags();
	});
	let ignoreWhitespace = (HINT.RULE_IGNORE_WHITESPACE && (flags & RULE_IGNORE_WHITESPACE));

	// Only reopen tags if we haven't exceeded our "fixing" budget
	let keepReopening = HINT.RULE_AUTO_REOPEN && (currentFixingCost < maxFixingCost),
		reopenTags    = [];
	closeTags.forEach((openTag) =>
	{
		let openTagName = openTag.getName();

		// Test whether this tag should be reopened automatically
		if (keepReopening)
		{
			if (openTag.getFlags() & RULE_AUTO_REOPEN)
			{
				reopenTags.push(openTag);
			}
			else
			{
				keepReopening = false;
			}
		}

		// Find the earliest position we can close this open tag
		let tagPos = tag.getPos();
		if (ignoreWhitespace)
		{
			tagPos = getMagicEndPos(tagPos);
		}

		// Output an end tag to close this start tag, then update the context
		let endTag = new Tag(Tag.END_TAG, openTagName, tagPos, 0);
		endTag.setFlags(openTag.getFlags());
		outputTag(endTag);
		popContext();
	});

	// Output our tag, moving the cursor past it, then update the context
	outputTag(tag);
	popContext();

	// If our fixing budget allows it, peek at upcoming tags and remove end tags that would
	// close tags that are already being closed now. Also, filter our list of tags being
	// reopened by removing those that would immediately be closed
	if (closeTags.length && currentFixingCost < maxFixingCost)
	{
		/**
		* @type {number} Rightmost position of the portion of text to ignore
		*/
		let ignorePos = pos;

		i = tagStack.length;
		while (--i >= 0 && ++currentFixingCost < maxFixingCost)
		{
			let upcomingTag = tagStack[i];

			// Test whether the upcoming tag is positioned at current "ignore" position and it's
			// strictly an end tag (not a start tag or a self-closing tag)
			if (upcomingTag.getPos() > ignorePos
			 || upcomingTag.isStartTag())
			{
				break;
			}

			// Test whether this tag would close any of the tags we're about to reopen
			let j = closeTags.length;

			while (--j >= 0 && ++currentFixingCost < maxFixingCost)
			{
				if (upcomingTag.canClose(closeTags[j]))
				{
					// Remove the tag from the lists and reset the keys
					closeTags.splice(j, 1);

					if (reopenTags[j])
					{
						reopenTags.splice(j, 1);
					}

					// Extend the ignored text to cover this tag
					ignorePos = Math.max(
						ignorePos,
						upcomingTag.getPos() + upcomingTag.getLen()
					);

					break;
				}
			}
		}

		if (ignorePos > pos)
		{
			/**
			* @todo have a method that takes (pos,len) rather than a Tag
			*/
			outputIgnoreTag(new Tag(Tag.SELF_CLOSING_TAG, 'i', pos, ignorePos - pos));
		}
	}

	// Re-add tags that need to be reopened, at current cursor position
	reopenTags.forEach((startTag) =>
	{
		let newTag = addCopyTag(startTag, pos, 0);

		// Re-pair the new tag
		let endTag = startTag.getEndTag();
		if (endTag)
		{
			newTag.pairWith(endTag);
		}
	});
}

/**
* Update counters and replace current context with its parent context
*/
function popContext()
{
	let tag = openTags.pop();
	--cntOpen[tag.getName()];
	context = context.parentContext;
}

/**
* Update counters and replace current context with a new context based on given tag
*
* If given tag is a self-closing tag, the context won't change
*
* @param {!Tag} tag Start tag (including self-closing)
*/
function pushContext(tag)
{
	let tagName   = tag.getName(),
		tagFlags  = tag.getFlags(),
		tagConfig = tagsConfig[tagName];

	++cntTotal[tagName];

	// If this is a self-closing tag, the context remains the same
	if (tag.isSelfClosingTag())
	{
		return;
	}

	// Recompute the allowed tags
	let allowed = [];
	context.allowed.forEach((v, k) =>
	{
		// If the current tag is not transparent, override the low bits (allowed children) of
		// current context with its high bits (allowed descendants)
		if (!HINT.RULE_IS_TRANSPARENT || !(tagFlags & RULE_IS_TRANSPARENT))
		{
			v = (v & 0xFF00) | (v >> 8);
		}
		allowed.push(tagConfig.allowed[k] & v);
	});

	// Use this tag's flags as a base for this context and add inherited rules
	let flags = tagFlags | (context.flags & RULES_INHERITANCE);

	// RULE_DISABLE_AUTO_BR turns off RULE_ENABLE_AUTO_BR
	if (flags & RULE_DISABLE_AUTO_BR)
	{
		flags &= ~RULE_ENABLE_AUTO_BR;
	}

	++cntOpen[tagName];
	openTags.push(tag);
	context         = { parentContext : context };
	context.allowed = allowed;
	context.flags   = flags;
}

/**
* Return whether given tag is allowed in current context
*
* @param  {string}  tagName
* @return {boolean}
*/
function tagIsAllowed(tagName)
{
	let n = tagsConfig[tagName].bitNumber;

	return !!(context.allowed[n >> 3] & (1 << (n & 7)));
}

//==========================================================================
// Tag stack
//==========================================================================

/**
* Add a start tag
*
* @param  {string}  name Name of the tag
* @param  {number}  pos  Position of the tag in the text
* @param  {number}  len  Length of text consumed by the tag
* @param  {number=} prio Tags' priority
* @return {!Tag}
*/
function addStartTag(name, pos, len, prio)
{
	return addTag(Tag.START_TAG, name, pos, len, prio || 0);
}

/**
* Add an end tag
*
* @param  {string}  name Name of the tag
* @param  {number}  pos  Position of the tag in the text
* @param  {number}  len  Length of text consumed by the tag
* @param  {number=} prio Tags' priority
* @return {!Tag}
*/
function addEndTag(name, pos, len, prio)
{
	return addTag(Tag.END_TAG, name, pos, len, prio || 0);
}

/**
* Add a self-closing tag
*
* @param  {string}  name Name of the tag
* @param  {number}  pos  Position of the tag in the text
* @param  {number}  len  Length of text consumed by the tag
* @param  {number=} prio Tags' priority
* @return {!Tag}
*/
function addSelfClosingTag(name, pos, len, prio)
{
	return addTag(Tag.SELF_CLOSING_TAG, name, pos, len, prio || 0);
}

/**
* Add a 0-width "br" tag to force a line break at given position
*
* @param  {number}  pos  Position of the tag in the text
* @param  {number=} prio Tags' priority
* @return {!Tag}
*/
function addBrTag(pos, prio)
{
	return addTag(Tag.SELF_CLOSING_TAG, 'br', pos, 0, prio || 0);
}

/**
* Add an "ignore" tag
*
* @param  {number}  pos  Position of the tag in the text
* @param  {number}  len  Length of text consumed by the tag
* @param  {number=} prio Tags' priority
* @return {!Tag}
*/
function addIgnoreTag(pos, len, prio)
{
	return addTag(Tag.SELF_CLOSING_TAG, 'i', pos, Math.min(len, textLen - pos), prio || 0);
}

/**
* Add a paragraph break at given position
*
* Uses a zero-width tag that is actually never output in the result
*
* @param  {number}  pos  Position of the tag in the text
* @param  {number=} prio Tags' priority
* @return {!Tag}
*/
function addParagraphBreak(pos, prio)
{
	return addTag(Tag.SELF_CLOSING_TAG, 'pb', pos, 0, prio || 0);
}

/**
* Add a copy of given tag at given position and length
*
* @param  {!Tag}    tag  Original tag
* @param  {number}  pos  Copy's position
* @param  {number}  len  Copy's length
* @param  {number=} prio Tags' priority
* @return {!Tag}         Copy tag
*/
function addCopyTag(tag, pos, len, prio)
{
	let copy = addTag(tag.getType(), tag.getName(), pos, len, tag.getSortPriority());
	copy.setAttributes(tag.getAttributes());

	return copy;
}

/**
* Add a tag
*
* @param  {number}  type Tag's type
* @param  {string}  name Name of the tag
* @param  {number}  pos  Position of the tag in the text
* @param  {number}  len  Length of text consumed by the tag
* @param  {number=} prio Tags' priority
* @return {!Tag}
*/
function addTag(type, name, pos, len, prio)
{
	// Create the tag
	let tag = new Tag(type, name, pos, len, prio || 0);

	// Set this tag's rules bitfield
	if (tagsConfig[name])
	{
		tag.setFlags(tagsConfig[name].rules.flags);
	}

	// Invalidate this tag if it's an unknown tag, a disabled tag, if either of its length or
	// position is negative or if it's out of bounds
	if ((!tagsConfig[name] && !tag.isSystemTag()) || isInvalidTextSpan(pos, len))
	{
		tag.invalidate();
	}
	else if (tagsConfig[name] && tagsConfig[name].isDisabled)
	{
		logger.warn(
			'Tag is disabled',
			{
				'tag'     : tag,
				'tagName' : name
			}
		);
		tag.invalidate();
	}
	else
	{
		insertTag(tag);
	}

	return tag;
}

/**
* Test whether given text span is outside text boundaries or an invalid UTF sequence
*
* @param  {number}  pos Start of text
* @param  {number}  len Length of text
* @return {boolean}
*/
function isInvalidTextSpan(pos, len)
{
	return (len < 0 || pos < 0 || pos + len > textLen || /[\uDC00-\uDFFF]/.test(text.substring(pos, pos + 1) + text.substring(pos + len, pos + len + 1)));
}

/**
* Insert given tag in the tag stack
*
* @param {!Tag} tag
*/
function insertTag(tag)
{
	if (!tagStackIsSorted)
	{
		tagStack.push(tag);
	}
	else
	{
		// Scan the stack and copy every tag to the next slot until we find the correct index
		let i   = tagStack.length,
			key = getSortKey(tag);
		while (i > 0 && key > getSortKey(tagStack[i - 1]))
		{
			tagStack[i] = tagStack[i - 1];
			--i;
		}
		tagStack[i] = tag;
	}
}

/**
* Add a pair of tags
*
* @param  {string} name     Name of the tags
* @param  {number} startPos Position of the start tag
* @param  {number} startLen Length of the start tag
* @param  {number} endPos   Position of the start tag
* @param  {number} endLen   Length of the start tag
* @param  {number=}  prio     Start tag's priority (the end tag will be set to minus that value)
* @return {!Tag}             Start tag
*/
function addTagPair(name, startPos, startLen, endPos, endLen, prio)
{
	// NOTE: the end tag is added first to try to keep the stack in the correct order
	let endTag   = addEndTag(name, endPos, endLen, -prio || 0),
		startTag = addStartTag(name, startPos, startLen, prio || 0);
	startTag.pairWith(endTag);

	return startTag;
}

/**
* Add a tag that represents a verbatim copy of the original text
*
* @param  {number} pos  Position of the tag in the text
* @param  {number} len  Length of text consumed by the tag
* @param  {number=} prio Tag's priority
* @return {!Tag}
*/
function addVerbatim(pos, len, prio)
{
	return addTag(Tag.SELF_CLOSING_TAG, 'v', pos, len, prio || 0);
}

/**
* Sort tags by position and precedence
*/
function sortTags()
{
	let arr  = {},
		keys = [],
		i    = tagStack.length;
	while (--i >= 0)
	{
		let tag = tagStack[i],
			key = getSortKey(tag, i);
		keys.push(key);
		arr[key] = tag;
	}
	keys.sort();

	i = keys.length;
	tagStack = [];
	while (--i >= 0)
	{
		tagStack.push(arr[keys[i]]);
	}

	tagStackIsSorted = true;
}

/**
* Generate a key for given tag that can be used to compare its position using lexical comparisons
*
* Tags are sorted by position first, then by priority, then by whether they consume any text,
* then by length, and finally in order of their creation.
*
* The stack's array is in reverse order. Therefore, tags that appear at the start of the text
* are at the end of the array.
*
* @param  {!Tag}    tag
* @param  {number=} tagIndex
* @return {string}
*/
function getSortKey(tag, tagIndex)
{
	// Ensure that negative values are sorted correctly by flagging them and making them positive
	let prioFlag = (tag.getSortPriority() >= 0),
		prio     = tag.getSortPriority();
	if (!prioFlag)
	{
		prio += (1 << 30);
	}

	// Sort 0-width tags separately from the rest
	let lenFlag = (tag.getLen() > 0),
		lenOrder;
	if (lenFlag)
	{
		// Inverse their length so that longest matches are processed first
		lenOrder = textLen - tag.getLen();
	}
	else
	{
		// Sort self-closing tags in-between start tags and end tags to keep them outside of tag
		// pairs
		let order = {};
		order[Tag.END_TAG]          = 0;
		order[Tag.SELF_CLOSING_TAG] = 1;
		order[Tag.START_TAG]        = 2;
		lenOrder = order[tag.getType()];
	}

	return hex32(tag.getPos()) + (+prioFlag) + hex32(prio) + (+lenFlag) + hex32(lenOrder) + hex32(tagIndex || 0);
}

/**
* Format given number to a 32 bit hex value
*
* @param  {number} number
* @return {string}
*/
function hex32(number)
{
	let hex = number.toString(16);

	return "        ".substring(hex.length) + hex;
}