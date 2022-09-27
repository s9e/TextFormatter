let setextLines = {};

function parse()
{
	matchSetextLines();

	let blocks       = [],
		blocksCnt    = 0,
		codeFence,
		codeIndent   = 4,
		codeTag,
		lineIsEmpty  = true,
		lists        = [],
		listsCnt     = 0,
		newContext   = false,
		textBoundary = 0,
		breakParagraph,
		continuation,
		ignoreLen,
		indentStr,
		indentLen,
		lfPos,
		listIndex,
		maxIndent,
		minIndent,
		blockDepth,
		tagPos,
		tagLen;

	// Capture all the lines at once so that we can overwrite newlines safely, without preventing
	// further matches
	let matches = [],
		m,
		regexp = /^(?:(?=[-*+\d \t>`~#_])((?: {0,3}>(?:(?!!)|!(?![^\n>]*?!<)) ?)+)?([ \t]+)?(\* *\* *\*[* ]*$|- *- *-[- ]*$|_ *_ *_[_ ]*$)?((?:[-*+]|\d+\.)[ \t]+(?=\S))?[ \t]*(#{1,6}[ \t]+|```+[^`\n]*$|~~~+[^~\n]*$)?)?/gm;
	while (m = regexp.exec(text))
	{
		matches.push(m);

		// Move regexp.lastIndex if the current match is empty
		if (m.index === regexp.lastIndex)
		{
			++regexp.lastIndex;
		}
	}

	matches.forEach((m) =>
	{
		let blockMarks = [],
			matchPos   = m.index,
			matchLen   = m[0].length,
			startPos,
			startLen,
			endPos,
			endLen;

		ignoreLen  = 0;
		blockDepth = 0;

		// If the last line was empty then this is not a continuation, and vice-versa
		continuation = !lineIsEmpty;

		// Capture the position of the end of the line and determine whether the line is empty
		lfPos       = text.indexOf("\n", matchPos);
		lineIsEmpty = (lfPos === matchPos + matchLen && !m[3] && !m[4] && !m[5]);

		// If the match is empty we need to move the cursor manually
		if (!matchLen)
		{
			++regexp.lastIndex;
		}

		// If the line is empty and it's the first empty line then we break current paragraph.
		breakParagraph = (lineIsEmpty && continuation);

		// Count block marks
		if (m[1])
		{
			blockMarks = getBlockMarks(m[1]);
			blockDepth = blockMarks.length;
			ignoreLen  = m[1].length;
			if (codeTag && codeTag.hasAttribute('blockDepth'))
			{
				blockDepth = Math.min(blockDepth, codeTag.getAttribute('blockDepth'));
				ignoreLen  = computeBlockIgnoreLen(m[1], blockDepth);
			}

			// Overwrite block markup
			overwrite(matchPos, ignoreLen);
		}

		// Close supernumerary blocks
		if (blockDepth < blocksCnt && !continuation)
		{
			newContext = true;
			do
			{
				let startTag = blocks.pop();
				addEndTag(startTag.getName(), textBoundary, 0).pairWith(startTag);
			}
			while (blockDepth < --blocksCnt);
		}

		// Open new blocks
		if (blockDepth > blocksCnt && !lineIsEmpty)
		{
			newContext = true;
			do
			{
				let tagName = (blockMarks[blocksCnt] === '>!') ? 'SPOILER' : 'QUOTE';
				blocks.push(addStartTag(tagName, matchPos, 0, -999));
			}
			while (blockDepth > ++blocksCnt);
		}

		// Compute the width of the indentation
		let indentWidth = 0,
			indentPos   = 0;
		if (m[2] && !codeFence)
		{
			indentStr = m[2];
			indentLen = indentStr.length;

			do
			{
				if (indentStr[indentPos] === ' ')
				{
					++indentWidth;
				}
				else
				{
					indentWidth = (indentWidth + 4) & ~3;
				}
			}
			while (++indentPos < indentLen && indentWidth < codeIndent);
		}

		// Test whether we're out of a code block
		if (codeTag && !codeFence && indentWidth < codeIndent && !lineIsEmpty)
		{
			newContext = true;
		}

		if (newContext)
		{
			newContext = false;

			// Close the code block if applicable
			if (codeTag)
			{
				if (textBoundary > codeTag.getPos())
				{
					// Overwrite the whole block
					overwrite(codeTag.getPos(), textBoundary - codeTag.getPos());
					codeTag.pairWith(addEndTag('CODE', textBoundary, 0, -1));
				}
				else
				{
					// The code block is empty
					codeTag.invalidate();
				}
				codeTag = null;
				codeFence = null;
			}

			// Close all the lists
			lists.forEach((list) =>
			{
				closeList(list, textBoundary);
			});
			lists    = [];
			listsCnt = 0;

			// Mark the block boundary
			if (matchPos)
			{
				markBoundary(matchPos - 1);
			}
		}

		if (indentWidth >= codeIndent)
		{
			if (codeTag || !continuation)
			{
				// Adjust the amount of text being ignored
				ignoreLen = (m[1] || '').length + indentPos;

				if (!codeTag)
				{
					// Create code block
					codeTag = addStartTag('CODE', matchPos + ignoreLen, 0, -999);
				}

				// Clear the captures to prevent any further processing
				m = {};
			}
		}
		else if (!codeTag)
		{
			let hasListItem = !!m[4];

			if (!indentWidth && !continuation && !hasListItem)
			{
				// Start of a new context
				listIndex = -1;
			}
			else if (continuation && !hasListItem)
			{
				// Continuation of current list item or paragraph
				listIndex = listsCnt - 1;
			}
			else if (!listsCnt)
			{
				// We're not inside of a list already, we can start one if there's a list item
				listIndex = (hasListItem) ? 0 : -1;
			}
			else
			{
				// We're inside of a list but we need to compute the depth
				listIndex = 0;
				while (listIndex < listsCnt && indentWidth > lists[listIndex].maxIndent)
				{
					++listIndex;
				}
			}

			// Close deeper lists
			while (listIndex < listsCnt - 1)
			{
				closeList(lists.pop(), textBoundary);
				--listsCnt;
			}

			// If there's no list item at current index, we'll need to either create one or
			// drop down to previous index, in which case we have to adjust maxIndent
			if (listIndex === listsCnt && !hasListItem)
			{
				--listIndex;
			}

			if (hasListItem && listIndex >= 0)
			{
				breakParagraph = true;

				// Compute the position and amount of text consumed by the item tag
				tagPos = matchPos + ignoreLen + indentPos;
				tagLen = m[4].length;

				// Create a LI tag that consumes its markup
				let itemTag = addStartTag('LI', tagPos, tagLen);

				// Overwrite the markup
				overwrite(tagPos, tagLen);

				// If the list index is within current lists count it means this is not a new
				// list and we have to close the last item. Otherwise, it's a new list that we
				// have to create
				if (listIndex < listsCnt)
				{
					addEndTag('LI', textBoundary, 0).pairWith(lists[listIndex].itemTag);

					// Record the item in the list
					lists[listIndex].itemTag = itemTag;
					lists[listIndex].itemTags.push(itemTag);
				}
				else
				{
					++listsCnt;

					if (listIndex)
					{
						minIndent = lists[listIndex - 1].maxIndent + 1;
						maxIndent = Math.max(minIndent, listIndex * 4);
					}
					else
					{
						minIndent = 0;
						maxIndent = indentWidth;
					}

					// Create a 0-width LIST tag right before the item tag LI
					let listTag = addStartTag('LIST', tagPos, 0);

					// Test whether the list item ends with a dot, as in "1."
					if (m[4].indexOf('.') > -1)
					{
						listTag.setAttribute('type', 'decimal');

						let start = +m[4];
						if (start !== 1)
						{
							listTag.setAttribute('start', start);
						}
					}

					// Record the new list depth
					lists.push({
						listTag   : listTag,
						itemTag   : itemTag,
						itemTags  : [itemTag],
						minIndent : minIndent,
						maxIndent : maxIndent,
						tight     : true
					});
				}
			}

			// If we're in a list, on a non-empty line preceded with a blank line...
			if (listsCnt && !continuation && !lineIsEmpty)
			{
				// ...and this is not the first item of the list...
				if (lists[0].itemTags.length > 1 || !hasListItem)
				{
					// ...every list that is currently open becomes loose
					lists.forEach((list) =>
					{
						list.tight = false;
					});
				}
			}

			codeIndent = (listsCnt + 1) * 4;
		}

		if (m[5])
		{
			// Headers
			if (m[5][0] === '#')
			{
				startLen = m[5].length;
				startPos = matchPos + matchLen - startLen;
				endLen   = getAtxHeaderEndTagLen(matchPos + matchLen, lfPos);
				endPos   = lfPos - endLen;

				addTagPair('H' + /#{1,6}/.exec(m[5])[0].length, startPos, startLen, endPos, endLen);

				// Mark the start and the end of the header as boundaries
				markBoundary(startPos);
				markBoundary(lfPos);

				if (continuation)
				{
					breakParagraph = true;
				}
			}
			// Code fence
			else if (m[5][0] === '`' || m[5][0] === '~')
			{
				tagPos = matchPos + ignoreLen;
				tagLen = lfPos - tagPos;

				if (codeTag && m[5] === codeFence)
				{
					codeTag.pairWith(addEndTag('CODE', tagPos, tagLen, -1));
					addIgnoreTag(textBoundary, tagPos - textBoundary);

					// Overwrite the whole block
					overwrite(codeTag.getPos(), tagPos + tagLen - codeTag.getPos());
					codeTag = null;
					codeFence = null;
				}
				else if (!codeTag)
				{
					// Create code block
					codeTag   = addStartTag('CODE', tagPos, tagLen);
					codeFence = m[5].replace(/[^`~]+/, '');
					codeTag.setAttribute('blockDepth', blockDepth);

					// Ignore the next character, which should be a newline
					addIgnoreTag(tagPos + tagLen, 1);

					// Add the language if present, e.g. ```php
					let lang = m[5].replace(/^[`~\s]*/, '').replace(/\s+$/, '');
					if (lang !== '')
					{
						codeTag.setAttribute('lang', lang);
					}
				}
			}
		}
		else if (m[3] && !listsCnt && text[matchPos + matchLen] !== "\x17")
		{
			// Horizontal rule
			addSelfClosingTag('HR', matchPos + ignoreLen, matchLen - ignoreLen);
			breakParagraph = true;

			// Mark the end of the line as a boundary
			markBoundary(lfPos);
		}
		else if (setextLines[lfPos] && setextLines[lfPos].blockDepth === blockDepth && !lineIsEmpty && !listsCnt && !codeTag)
		{
			// Setext-style header
			addTagPair(
				setextLines[lfPos].tagName,
				matchPos + ignoreLen,
				0,
				setextLines[lfPos].endPos,
				setextLines[lfPos].endLen
			);

			// Mark the end of the Setext line
			markBoundary(setextLines[lfPos].endPos + setextLines[lfPos].endLen);
		}

		if (breakParagraph)
		{
			addParagraphBreak(textBoundary);
			markBoundary(textBoundary);
		}

		if (!lineIsEmpty)
		{
			textBoundary = lfPos;
		}

		if (ignoreLen)
		{
			addIgnoreTag(matchPos, ignoreLen, 1000);
		}
	});
}

/**
* Close a list at given offset
*
* @param {!Object} list
* @param {number}  textBoundary
*/
function closeList(list, textBoundary)
{
	addEndTag('LIST', textBoundary, 0).pairWith(list.listTag);
	addEndTag('LI',   textBoundary, 0).pairWith(list.itemTag);

	if (list.tight)
	{
		list.itemTags.forEach((itemTag) =>
		{
			itemTag.removeFlags(RULE_CREATE_PARAGRAPHS);
		});
	}
}

/**
* Compute the amount of text to ignore at the start of a block line
*
* @param  {string} str           Original block markup
* @param  {number} maxBlockDepth Maximum block depth
* @return {number}               Number of characters to ignore
*/
function computeBlockIgnoreLen(str, maxBlockDepth)
{
	let remaining = str;
	while (--maxBlockDepth >= 0)
	{
		remaining = remaining.replace(/^ *>!? ?/, '');
	}

	return str.length - remaining.length;
}

/**
* Return the length of the markup at the end of an ATX header
*
* @param  {number} startPos Start of the header's text
* @param  {number} endPos   End of the header's text
* @return {number}
*/
function getAtxHeaderEndTagLen(startPos, endPos)
{
	let content = text.substring(startPos, endPos),
		m = /[ \t]*#*[ \t]*$/.exec(content);

	return m[0].length;
}

/**
* Capture and return block marks from given string
*
* @param  {string} str Block markup, composed of ">", "!" and whitespace
* @return {!Array<string>}
*/
function getBlockMarks(str)
{
	let blockMarks = [],
		regexp     = />!?/g,
		m;
	while (m = regexp.exec(str))
	{
		blockMarks.push(m[0]);
	}

	return blockMarks;
}

/**
* Capture and store lines that contain a Setext-tyle header
*/
function matchSetextLines()
{
	// Capture the underlines used for Setext-style headers
	if (text.indexOf('-') === -1 && text.indexOf('=') === -1)
	{
		return;
	}

	// Capture the any series of - or = alone on a line, optionally preceded with the
	// angle brackets notation used in block markup
	let m, regexp = /^(?=[-=>])(?:>!? ?)*(?=[-=])(?:-+|=+) *$/gm;

	while (m = regexp.exec(text))
	{
		let match    = m[0],
			matchPos = m.index;

		// Compute the position of the end tag. We start on the LF character before the
		// match and keep rewinding until we find a non-space character
		let endPos = matchPos - 1;
		while (endPos > 0 && text[endPos - 1] === ' ')
		{
			--endPos;
		}

		// Store at the offset of the LF character
		setextLines[matchPos - 1] = {
			endLen     : matchPos + match.length - endPos,
			endPos     : endPos,
			blockDepth : match.length - match.replace(/>/g, '').length,
			tagName    : (match[0] === '=') ? 'H1' : 'H2'
		};
	}
}