/**
* @type {!boolean} Whether the output contains "rich" tags, IOW any tag that is not <i> or <br/>
*/
var isRich;

/**
* @type {!Object} Associative array of namespace prefixes in use in document (prefixes used as key)
*/
var namespaces;

/**
* @type {!string} This parser's output
*/
var output;

/**
* @type {!number} Position before which we output text verbatim, without paragraphs or linebreaks
*/
var wsPos;

// TODO: replace/merge?
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

	// Output the rest of the text
	if (pos < textLen)
	{
		outputText(textLen, 0, true);
	}

	// Close the last paragraph if applicable
	outputParagraphEnd();

	// Remove empty tag pairs, e.g. <I><U></U></I> as well as empty paragraphs
	do
	{
		tmp = output;
		output = output.replace(/<((?:\w+:)?\w+)[^>]*><\/\1>/g, '');
	}
	while (output !== tmp);

	// Merge consecutive <i> tags
	output = output.replace(/<\/i><i>/g, '', output);

	// Use a <rt> root if the text is rich, or <pt> for plain text (including <p></p> and <br/>)
	var tagName = (isRich) ? 'rt' : 'pt';

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
		tagConfig  = tagsConfig[tagName],
		skipBefore = 0,
		skipAfter  = 0;

	if (HINT.RULE_TRIM_WHITESPACE && tagConfig.rules.flags & RULE_TRIM_WHITESPACE)
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
		if (HINT.RULE_BREAK_PARAGRAPH && tagConfig.rules.flags & RULE_BREAK_PARAGRAPH)
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
		if (HINT.RULE_BREAK_PARAGRAPH && tagConfig.rules.flags & RULE_BREAK_PARAGRAPH)
		{
			outputParagraphEnd();
		}
		else
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
		var attributes = tag.getAttributes();

		for (var attrName in attributes)
		{
			output += ' ' + attrName + '="' + htmlspecialchars_compat(attributes[attrName].toString()) + '"';
		}

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
			output += '><st>' + tagText + '</st>';
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
			output += '<et>' + tagText + '</et>';
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

		if (skipPos === catchupPos)
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

	// Start a paragraph if applicable
	outputParagraphStart(catchupPos);

	// Compute the amount of text to ignore at the end of the output
	var ignorePos = catchupPos,
		ignoreLen = 0;

	// Ignore newlines at the end of the text if we're going to close the paragraph
	if (closeParagraph && context.inParagraph)
	{
		while (--ignorePos >= 0 && text.charAt(ignorePos) === "\n")
		{
			++ignoreLen;
		}
	}

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
	if (maxPos > pos)
	{
		var ignoreText = /^[ \n\t]*/.exec(text)[0];

		if (ignoreText !== '')
		{
			output += ignoreText;
			pos += ignoreText.length;
		}
	}

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