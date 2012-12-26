/**
* @type {!boolean} Whether the output contains "rich" tags, IOW any tag that is not <i> or <br/>
*/
var isRich;

/**
* @type {!Object} Associative array of namespace prefixes in use in document (prefixes used as key)
*/
var namespaces;

/**
* Finalize the output by appending the rest of the unprocessed text and create the root node
*/
function finalizeOutput()
{
	// Output the rest of the text
	if (pos < textLen)
	{
		outputText(textLen, 0);
	}

	// Merge consecutive <i> tags
	output = output.replace(/<\/i><i>/g, '', output);

	// Use a <rt> root if the text is rich, or <pt> for plain text (including <i> and <br/>)
	var tagName = (isRich) ? 'rt' : 'pt';

	// Prepare the root node with all the namespace declarations
	var tmp = '<' . tagName;
	for (var prefix in namespaces)
	{
		tmp += ' xmlns:' + prefix + '="urn:s9e:TextFormatter:' + prefix . '"';
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
	tagText = (tagLen)
			 ? htmlspecialchars(substr(text, tagPos, tagLen), ENT_NOQUOTES, 'UTF-8')
			 : '';

	// Output current tag
	if (tag.isStartTag())
	{
		// Record this tag's namespace, if applicable
		colonPos = strpos(tagName, ':');
		if (colonPos)
		{
			namespaces[substr(tagName, 0, colonPos)] = 0;
		}

		// Open the start tag and add its attributes, but don't close the tag
		output .= '<' . tagName;
		foreach (tag.getAttributes() as attrName => attrValue)
		{
			output .= ' ' . attrName . '="' . htmlspecialchars(attrValue, ENT_COMPAT, 'UTF-8') . '"';
		}

		if (tag.isSelfClosingTag())
		{
			if (tagLen)
			{
				output .= '>' . tagText . '</' . tagName . '>';
			}
			else
			{
				output .= '/>';
			}
		}
		elseif (tagLen)
		{
			output .= '><st>' . tagText . '</st>';
		}
		else
		{
			output .= '>';
		}
	}
	else
	{
		if (tagLen)
		{
			output .= '<et>' . tagText . '</et>';
		}

		output .= '</' . tagName . '>';
	}

	// Move the cursor past the tag
	pos = tagPos + tagLen;

	// Trim newlines (no other whitespace) after this tag
	ignorePos = pos;
	while (trimAfter && ignorePos < textLen && text[ignorePos] === "\n")
	{
		// Decrement the number of lines to trim
		--trimAfter;

		// Move the cursor past the newline
		++ignorePos;
	}

	if (ignorePos !== pos)
	{
		output .= '<i>' . substr(text, pos, ignorePos - pos) . '</i>';
		pos = ignorePos;
	}
}

/**
* Output the text between the cursor's position (included) and given position (not included)
*
* @param  integer catchupPos Position we're catching up to
* @param  integer maxLines   Maximum number of lines to trim at the end of the text
* @return void
*/
protected function outputText(catchupPos, maxLines)
{
	if (pos >= catchupPos)
	{
		// We're already there
		return;
	}

	catchupLen  = catchupPos - pos;
	catchupText = substr(text, pos, catchupLen);
	pos   = catchupPos;

	if (context['flags'] & self::RULE_IGNORE_TEXT)
	{
		output .= '<i>' . catchupText . '</i>';
		return;
	}

	ignorePos = catchupLen;
	ignoreLen = 0;
	while (maxLines && --ignorePos >= 0)
	{
		c = catchupText[ignorePos];
		if (strpos(" \n\t", c) === false)
		{
			break;
		}

		if (c === "\n")
		{
			--maxLines;
		}

		++ignoreLen;
	}

	if (ignoreLen)
	{
		ignoreText  = '<i>' . substr(catchupText, -ignoreLen) . '</i>';
		catchupText = substr(catchupText, 0, catchupLen - ignoreLen);
	}
	else
	{
		ignoreText = '';
	}

	catchupText = htmlspecialchars(catchupText, ENT_NOQUOTES, 'UTF-8');
	if (!(context['flags'] & self::RULE_NO_BR_CHILD))
	{
		catchupText = str_replace("\n", "<br/>\n", catchupText);
	}

	output .= catchupText . ignoreText;
}

/**
* Output a linebreak tag
*
* @param  Tag  tag
* @return void
*/
protected function outputBrTag(Tag tag)
{
	outputText(tag.getPos(), 0);
	output .= '<br/>';
}

/**
* Output an ignore tag
*
* @param  Tag  tag
* @return void
*/
protected function outputIgnoreTag(Tag tag)
{
	tagPos = tag.getPos();
	tagLen = tag.getLen();

	// Capture the text to ignore
	ignoreText = substr(text, tagPos, tagLen);

	// Catch up with the tag's position then output the tag
	outputText(tagPos, 0);
	output .= '<i>' . htmlspecialchars(ignoreText, ENT_NOQUOTES, 'UTF-8') . '</i>';

	// Move the cursor past this tag
	pos = tagPos + tagLen;
}