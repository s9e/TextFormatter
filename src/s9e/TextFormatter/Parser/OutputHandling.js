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
		outputText(textLen, 0);
	}

	// Remove empty tag pairs, e.g. <I><U></U></I>
	do
	{
		tmp = output;
		output = output.replace(/<((?:\w+:)?\w+)[^>]*><\/\1>/g, '');
	}
	while (output !== tmp);

	// Merge consecutive <i> tags
	output = output.replace(/<\/i><i>/g, '', output);

	// Use a <rt> root if the text is rich, or <pt> for plain text (including <i> and <br/>)
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

	var tagName   = tag.getName(),
		tagPos    = tag.getPos(),
		tagLen    = tag.getLen(),
		tagConfig = tagsConfig[tagName],
		trimBefore = 0,
		trimAfter  = 0;

	if (tagConfig.rules.flags & RULE_TRIM_WHITESPACE)
	{
		trimBefore = (tag.isStartTag()) ? 2 : 1;
		trimAfter  = (tag.isEndTag())   ? 2 : 1;
	}

	// Let the cursor catch up with this tag's position
	outputText(tagPos, trimBefore);

	// Capture the text consumed by the tag
	var tagText = (tagLen)
				? htmlspecialchars_noquotes(text.substr(tagPos, tagLen))
				: '';

	// Output current tag
	if (tag.isStartTag())
	{
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

	// Trim newlines (no other whitespace) after this tag
	var ignorePos = pos;
	while (trimAfter && ignorePos < textLen && text.charAt(ignorePos) === "\n")
	{
		// Decrement the number of lines to trim
		--trimAfter;

		// Move the cursor past the newline
		++ignorePos;
	}

	if (ignorePos !== pos)
	{
		output += '<i>' + text.substr(pos, ignorePos - pos) + '</i>';
		pos = ignorePos;
	}
}

/**
* Output the text between the cursor's position (included) and given position (not included)
*
* @param  {!number} catchupPos Position we're catching up to
* @param  {!number} maxLines   Maximum number of lines to trim at the end of the text
*/
function outputText(catchupPos, maxLines)
{
	if (pos >= catchupPos)
	{
		// We're already there
		return;
	}

	var catchupLen  = catchupPos - pos,
		catchupText = text.substr(pos, catchupLen);

	pos = catchupPos;

	if (context.flags & RULE_IGNORE_TEXT)
	{
		output += '<i>' + catchupText + '</i>';
		return;
	}

	var ignorePos = catchupLen,
		ignoreLen = 0;
	while (maxLines && --ignorePos >= 0)
	{
		var c = catchupText.charAt(ignorePos);
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

	var ignoreText = '';
	if (ignoreLen)
	{
		// TODO: IE compat
		ignoreText  = '<i>' + catchupText.substr(-ignoreLen) + '</i>';
		catchupText = catchupText.substr(0, catchupLen - ignoreLen);
	}

	catchupText = htmlspecialchars_noquotes(catchupText);
	if (!(context.flags & RULE_NO_BR_CHILD))
	{
		catchupText = catchupText.replace(/\n/g, "<br/>\n");
	}

	output += catchupText + ignoreText;
}

/**
* Output a linebreak tag
*
* @param  {!Tag} tag
* @return void
*/
function outputBrTag(tag)
{
	outputText(tag.getPos(), 0);
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
	outputText(tagPos, 0);
	output += '<i>' + htmlspecialchars_noquotes(ignoreText) + '</i>';
	isRich = true;

	// Move the cursor past this tag
	pos = tagPos + tagLen;
}