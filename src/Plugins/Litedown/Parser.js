var hasEscapedChars, startTagLen, startTagPos, endTagPos, endTagLen;

// Unlike the PHP parser, init() must not take an argument
init();

// Match block-level markup as well as forced line breaks
matchBlockLevelMarkup();

// Inline code must be done first to avoid false positives in other markup
matchInlineCode();

// Images must be matched before links
matchImages();

// Do the rest of inline markup
matchInlineLinks();
matchStrikethrough();
matchSuperscript();
matchEmphasis();
matchForcedLineBreaks();

/**
* Close a list at given offset
*
* @param  {!Array}  list
* @param  {!number} textBoundary
*/
function closeList(list, textBoundary)
{
	addEndTag('LIST', textBoundary, 0).pairWith(list.listTag);
	addEndTag('LI',   textBoundary, 0).pairWith(list.itemTag);

	if (list.tight)
	{
		list.itemTags.forEach(function(itemTag)
		{
			itemTag.removeFlags(RULE_CREATE_PARAGRAPHS);
		});
	}
}

/**
* Decode a chunk of encoded text to be used as an attribute value
*
* Decodes escaped literals and removes slashes and 0x1A characters
*
* @param  {!string}  str Encoded text
* @return {!string}      Decoded text
*/
function decode(str)
{
	str = str.replace(/[\\\x1A]/g, '');

	if (hasEscapedChars)
	{
		str = str.replace(
			/\x1B./g,
			function (seq)
			{
				return {
					"\x1B0": '!', "\x1B1": '"',  "\x1B2": ')', "\x1B3": '*',
					"\x1B4": '[', "\x1B5": '\\', "\x1B6": ']', "\x1B7": '^',
					"\x1B8": '_', "\x1B9": '`',  "\x1BA": '~'
				}[seq];
			}
		);
	}

	return str;
}

/**
* Encode escaped literals that have a special meaning
*
* @param  {!string}  str Original text
* @return {!string}      Encoded text
*/
function encode(str)
{
	return str.replace(
		/\\[!")*[\\\]^_`~]/g,
		function (str)
		{
			return {
				'\\!': "\x1B0", '\\"': "\x1B1", '\\)':  "\x1B2",
				'\\*': "\x1B3", '\\[': "\x1B4", '\\\\': "\x1B5",
				'\\]': "\x1B6", '\\^': "\x1B7", '\\_':  "\x1B8",
				'\\`': "\x1B9", '\\~': "\x1BA"
			}[str];
		}
	);
}

/**
* Return the length of the markup at the end of an ATX header
*
* @param  {!number} startPos Start of the header's text
* @param  {!number} endPos   End of the header's text
* @return {!number}
*/
function getAtxHeaderEndTagLen(startPos, endPos)
{
	var content = text.substr(startPos, endPos - startPos),
		m = /[ \t]*#*[ \t]*$/.exec(content);

	return m[0].length;
}

/**
* Get emphasis markup split by block
*
* @param  {!RegExp} regexp Regexp used to match emphasis
* @param  {!number} pos    Position in the text of the first emphasis character
* @return {!Array}         Each array contains a list of [matchPos, matchLen] pairs
*/
function getEmphasisByBlock(regexp, pos)
{
	var block    = [],
		blocks   = [],
		breakPos = breakPos  = text.indexOf("\x17", pos),
		m;

	regexp.lastIndex = pos;
	while (m = regexp.exec(text))
	{
		var matchPos = m['index'],
			matchLen = m[0].length;

		// Test whether we've just passed the limits of a block
		if (matchPos > breakPos)
		{
			blocks.push(block);
			block    = [];
			breakPos = text.indexOf("\x17", matchPos);
		}

		// Test whether we should ignore this markup
		if (!ignoreEmphasis(matchPos, matchLen))
		{
			block.push([matchPos, matchLen]);
		}
	}
	blocks.push(block);

	return blocks;
}

/**
* Capture lines that contain a Setext-tyle header
*
* @return {!Object}
*/
function getSetextLines()
{
	var setextLines = {};

	// Capture the underlines used for Setext-style headers
	if (text.indexOf('-') === -1 && text.indexOf('=') === -1)
	{
		return setextLines;
	}

	// Capture the any series of - or = alone on a line, optionally preceded with the
	// angle brackets notation used in blockquotes
	var m, regexp = /^(?=[-=>])(?:> ?)*(?=[-=])(?:-+|=+) *$/gm;

	while (m = regexp.exec(text))
	{
		var match    = m[0],
			matchPos = m['index'];

		// Compute the position of the end tag. We start on the LF character before the
		// match and keep rewinding until we find a non-space character
		var endTagPos = matchPos - 1;
		while (endTagPos > 0 && text[endTagPos - 1] === ' ')
		{
			--endTagPos;
		}

		// Store at the offset of the LF character
		setextLines[matchPos - 1] = {
			endTagLen  : matchPos + match.length - endTagPos,
			endTagPos  : endTagPos,
			quoteDepth : match.length - match.replace(/>/g, '').length,
			tagName    : (match.charAt(0) === '=') ? 'H1' : 'H2'
		};
	}

	return setextLines;
}

/**
* Test whether emphasis should be ignored at the given position in the text
*
* @param  {!number}  matchPos Position of the emphasis in the text
* @param  {!number}  matchLen Length of the emphasis
* @return {!boolean}
*/
function ignoreEmphasis(matchPos, matchLen)
{
	// Ignore single underscores between alphanumeric characters
	if (text[matchPos] === '_' && matchLen === 1 && isSurroundedByAlnum(matchPos, matchLen))
	{
		return true;
	}

	return false;
}

/**
* Initialize this parser
*/
function init()
{
	if (text.indexOf('\\') < 0)
	{
		hasEscapedChars = false;
	}
	else
	{
		hasEscapedChars = true;

		// Encode escaped literals that have a special meaning otherwise, so that we don't have
		// to take them into account in regexps
		text = encode(text);
	}

	// We append a couple of lines and a non-whitespace character at the end of the text in
	// order to trigger the closure of all open blocks such as quotes and lists
	text += "\n\n\x17";
}

/**
* Test whether given character is alphanumeric
*
* @param  {!string}  chr
* @return {!boolean}
*/
function isAlnum(chr)
{
	return (' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'.indexOf(chr) > 0);
}

/**
* Test whether a length of text is surrounded by alphanumeric characters
*
* @param  {!number}  matchPos Start of the text
* @param  {!number}  matchLen Length of the text
* @return {!boolean}
*/
function isSurroundedByAlnum(matchPos, matchLen)
{
	return (matchPos > 0 && isAlnum(text[matchPos - 1]) && isAlnum(text[matchPos + matchLen]));
}

/**
* Match block-level markup, as well as forced line breaks and headers
*/
function matchBlockLevelMarkup()
{
	var boundaries   = [],
		codeFence,
		codeIndent   = 4,
		codeTag,
		lineIsEmpty  = true,
		lists        = [],
		listsCnt     = 0,
		newContext   = false,
		quotes       = [],
		quotesCnt    = 0,
		setextLines  = getSetextLines(),
		textBoundary = 0,
		breakParagraph,
		continuation,
		endTag,
		ignoreLen,
		indentStr,
		indentLen,
		lfPos,
		listIndex,
		maxIndent,
		minIndent,
		quoteDepth,
		tagPos,
		tagLen;

	// Capture all the lines at once so that we can overwrite newlines safely, without preventing
	// further matches
	var matches = [],
		m,
		regexp = /^(?:(?=[-*+\d \t>`~#_])((?: {0,3}> ?)+)?([ \t]+)?(\* *\* *\*[* ]*$|- *- *-[- ]*$|_ *_ *_[_ ]*$)?((?:[-*+]|\d+\.)[ \t]+(?=\S))?[ \t]*(#{1,6}[ \t]+|```+.*|~~~+.*)?)?/gm;
	while (m = regexp.exec(text))
	{
		matches.push(m);

		// Move regexp.lastIndex if the current match is empty
		if (m['index'] === regexp['lastIndex'])
		{
			++regexp['lastIndex'];
		}
	}

	matches.forEach(function(m)
	{
		var matchPos  = m['index'],
			matchLen  = m[0].length;

		ignoreLen = 0;

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

		// Count quote marks
		if (m[1])
		{
			quoteDepth = m[1].length - m[1].replace(/>/g, '').length;
			ignoreLen  = m[1].length;
		}
		else
		{
			quoteDepth = 0;
		}

		// Close supernumerary quotes
		if (quoteDepth < quotesCnt && !continuation && !lineIsEmpty)
		{
			newContext = true;

			do
			{
				addEndTag('QUOTE', textBoundary, 0).pairWith(quotes.pop());
			}
			while (quoteDepth < --quotesCnt);
		}

		// Open new quotes
		if (quoteDepth > quotesCnt && !lineIsEmpty)
		{
			newContext = true;

			do
			{
				var tag = addStartTag('QUOTE', matchPos, 0);
				tag.setSortPriority(quotesCnt);

				quotes.push(tag);
			}
			while (quoteDepth > ++quotesCnt);
		}

		// Compute the width of the indentation
		var indentWidth = 0,
			indentPos   = 0;
		if (m[2])
		{
			indentStr = m[2];
			indentLen = indentStr.length;
			maxIndent = (codeFence) ? 0 : codeIndent;

			do
			{
				if (indentStr.charAt(indentPos) === ' ')
				{
					++indentWidth;
				}
				else
				{
					indentWidth = (indentWidth + 4) & ~3;
				}
			}
			while (++indentPos < indentLen && indentWidth < maxIndent);
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
				// Overwrite the whole block
				overwrite(codeTag.getPos(), textBoundary - codeTag.getPos());

				endTag = addEndTag('CODE', textBoundary, 0);
				endTag.pairWith(codeTag);
				endTag.setSortPriority(-1);
				codeTag = null;
				codeFence = null;
			}

			// Close all the lists
			lists.forEach(function(list)
			{
				closeList(list, textBoundary);
			});
			lists    = [];
			listsCnt = 0;

			// Mark the block boundary
			if (matchPos)
			{
				boundaries.push(matchPos - 1);
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
					codeTag = addStartTag('CODE', matchPos + ignoreLen, 0);
				}

				// Clear the captures to prevent any further processing
				m = {};
			}
		}
		else
		{
			var hasListItem = !!m[4];

			if (!indentWidth && !continuation && !hasListItem && !lineIsEmpty)
			{
				// Start of a new paragraph
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
				// and it's not in continuation of a paragraph
				if (!continuation && hasListItem)
				{
					// Start of a new list
					listIndex = 0;
				}
				else
				{
					// We're in a normal paragraph
					listIndex = -1;
				}
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
				tagPos = matchPos + ignoreLen + indentPos
				tagLen = m[4].length;

				// Create a LI tag that consumes its markup
				var itemTag = addStartTag('LI', tagPos, tagLen);

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
					var listTag = addStartTag('LIST', tagPos, 0);

					// Test whether the list item ends with a dot, as in "1."
					if (m[4].indexOf('.') > -1)
					{
						listTag.setAttribute('type', 'decimal');
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
					lists.forEach(function(list)
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
			if (m[5].charAt(0) === '#')
			{
				startTagLen = m[5].length;
				startTagPos = matchPos + matchLen - startTagLen;
				endTagLen   = getAtxHeaderEndTagLen(matchPos + matchLen, lfPos);
				endTagPos   = lfPos - endTagLen;

				addTagPair('H' + /#{1,6}/.exec(m[5])[0].length, startTagPos, startTagLen, endTagPos, endTagLen);

				// Mark the start and the end of the header as boundaries
				boundaries.push(startTagPos);
				boundaries.push(endTagPos);

				if (continuation)
				{
					breakParagraph = true;
				}
			}
			// Code fence
			else if (m[5].charAt(0) === '`' || m[5].charAt(0) === '~')
			{
				tagPos = matchPos + ignoreLen;
				tagLen = lfPos - tagPos;

				if (codeTag && m[5].charAt(0) === codeFence)
				{
					endTag = addEndTag('CODE', tagPos, tagLen);
					endTag.pairWith(codeTag);
					endTag.setSortPriority(-1);

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
					codeFence = m[5].charAt(0);

					// Ignore the next character, which should be a newline
					addIgnoreTag(tagPos + tagLen, 1);

					// Add the language if present, e.g. ```php
					var lang = m[5].replace(/^[`~]*/, '');
					if (lang !== '')
					{
						codeTag.setAttribute('lang', lang);
					}
				}
			}
		}
		else if (m[3] && !listsCnt)
		{
			// Horizontal rule
			addSelfClosingTag('HR', matchPos + ignoreLen, matchLen - ignoreLen);
			breakParagraph = true;

			// Overwrite the LF to prevent forced line breaks from matching
			overwrite(lfPos, 1);
		}
		else if (setextLines[lfPos] && setextLines[lfPos].quoteDepth === quoteDepth && !lineIsEmpty && !listsCnt && !codeTag)
		{
			// Setext-style header
			addTagPair(
				setextLines[lfPos].tagName,
				matchPos + ignoreLen,
				0,
				setextLines[lfPos].endTagPos,
				setextLines[lfPos].endTagLen
			);

			// Overwrite the LF to prevent forced line breaks from matching
			overwrite(lfPos, 1);
		}

		if (breakParagraph)
		{
			addParagraphBreak(textBoundary);
			boundaries.push(textBoundary);
		}

		if (!lineIsEmpty)
		{
			textBoundary = lfPos;
		}

		if (ignoreLen)
		{
			addIgnoreTag(matchPos, ignoreLen).setSortPriority(1000);
		}
	});

	boundaries.forEach(function(pos)
	{
		text = text.substr(0, pos) + "\x17" + text.substr(1 + pos);
	});
}

/**
* Match all forms of emphasis (emphasis and strong, using underscores or asterisks)
*/
function matchEmphasis()
{
	matchEmphasisByCharacter('*', /\*+/g);
	matchEmphasisByCharacter('_', /_+/g);
}

/**
* Match emphasis and strong applied using given character
*
* @param  {!string} character Markup character, either * or _
* @param  {!RegExp} regexp    Regexp used to match the series of emphasis character
*/
function matchEmphasisByCharacter(character, regexp)
{
	var pos = text.indexOf(character);
	if (pos === -1)
	{
		return;
	}

	getEmphasisByBlock(regexp, pos).forEach(processEmphasisBlock);
}

/**
* Match forced line break
*/
function matchForcedLineBreaks()
{
	var pos = text.indexOf("  \n");
	while (pos !== -1)
	{
		addBrTag(pos + 2);
		pos = text.indexOf("  \n", pos + 3);
	}
}

/**
* Match images markup
*/
function matchImages()
{
	if (text.indexOf('![') === -1)
	{
		return;
	}

	var m, regexp = /!\[([^\x17\]]*)] ?\(([^\x17 ")]+)(?: "([^\x17"]*)")?\)/g;
	while (m = regexp.exec(text))
	{
		var matchPos    = m['index'],
			matchLen    = m[0].length,
			contentLen  = m[1].length,
			startTagPos = matchPos,
			startTagLen = 2,
			endTagPos   = startTagPos + startTagLen + contentLen,
			endTagLen   = matchLen - startTagLen - contentLen;

		var startTag = addTagPair('IMG', startTagPos, startTagLen, endTagPos, endTagLen);
		startTag.setAttribute('alt', decode(m[1]));
		startTag.setAttribute('src', decode(m[2]));

		if (m[3] > '')
		{
			startTag.setAttribute('title', decode(m[3]));
		}

		// Overwrite the markup
		overwrite(matchPos, matchLen);
	}
}

/**
* Match inline code
*/
function matchInlineCode()
{
	if (text.indexOf('`') === -1)
	{
		return;
	}

	var m, regexp = /(``?)[^\x17]*?[^`]\1(?!`)/g;
	while (m = regexp.exec(text))
	{
		var matchPos = m['index'],
			matchLen = m[0].length,
			tagLen   = m[1].length;

		addTagPair('C', matchPos, tagLen, matchPos + matchLen - tagLen, tagLen);

		// Overwrite the markup
		overwrite(matchPos, matchLen);
	}
}

/**
* Match inline links
*/
function matchInlineLinks()
{
	if (text.indexOf('[') === -1)
	{
		return;
	}

	var m, regexp = /\[([^\x17\]]+)] ?\(([^\x17 ()]+(?:\([^\x17 ()]+\)[^\x17 ()]*)*[^\x17 )]*)(?: "(.*?)")?\)/g;
	while (m = regexp.exec(text))
	{
		var matchPos    = m['index'],
			matchLen    = m[0].length,
			contentLen  = m[1].length,
			startTagPos = matchPos,
			startTagLen = 1,
			endTagPos   = startTagPos + startTagLen + contentLen,
			endTagLen   = matchLen - startTagLen - contentLen;

		var tag = addTagPair('URL', startTagPos, startTagLen, endTagPos, endTagLen);
		tag.setAttribute('url', decode(m[2]));

		if (m[3] > '')
		{
			tag.setAttribute('title', decode(m[3]));
		}

		// Give the link a slightly better priority to give it precedence over
		// possible BBCodes such as [b](https://en.wikipedia.org/wiki/B)
		tag.setSortPriority(-1);

		// Overwrite the markup without touching the link's text
		overwrite(startTagPos, startTagLen);
		overwrite(endTagPos,   endTagLen);
	}
}

/**
* Match strikethrough
*/
function matchStrikethrough()
{
	if (text.indexOf('~~') === -1)
	{
		return;
	}

	var m, regexp = /~~[^\x17]+?~~/g;
	while (m = regexp.exec(text))
	{
		var match    = m[0],
			matchPos = m['index'],
			matchLen = match.length;

		addTagPair('DEL', matchPos, 2, matchPos + matchLen - 2, 2);
	}
}

/**
* Match superscript
*/
function matchSuperscript()
{
	if (text.indexOf('^') === -1)
	{
		return;
	}

	var m, regexp = /\^[^\x17\s]+/g;
	while (m = regexp.exec(text))
	{
		var match       = m[0],
			matchPos    = m['index'],
			matchLen    = match.length,
			startTagPos = matchPos,
			endTagPos   = matchPos + matchLen;

		var parts = match.split('^');
		parts.shift();

		parts.forEach(function(part)
		{
			addTagPair('SUP', startTagPos, 1, endTagPos, 0);
			startTagPos += 1 + part.length;
		});
	}
}

/**
* Overwrite part of the text with substitution characters ^Z (0x1A)
*
* @param  {!number} pos Start of the range
* @param  {!number} len Length of text to overwrite
*/
function overwrite(pos, len)
{
	text = text.substr(0, pos) + new Array(1 + len).join("\x1A") + text.substr(pos + len);
}

/**
* Process a list of emphasis markup strings
*
* @param {!Array<!Array<!number>>} block List of [matchPos, matchLen] pairs
*/
function processEmphasisBlock(block)
{
	var buffered  = 0,
		emPos     = -1,
		strongPos = -1,
		pair,
		remaining;

	block.forEach(function(pair)
	{
		var matchPos     = pair[0],
			matchLen     = pair[1],
			closeLen     = Math.min(3, matchLen),
			closeEm      = closeLen & buffered & 1,
			closeStrong  = closeLen & buffered & 2,
			emEndPos     = matchPos,
			strongEndPos = matchPos;

		if (buffered > 2 && emPos === strongPos)
		{
			if (closeEm)
			{
				emPos += 2;
			}
			else
			{
				++strongPos;
			}
		}

		if (closeEm && closeStrong)
		{
			if (emPos < strongPos)
			{
				emEndPos += 2;
			}
			else
			{
				++strongEndPos;
			}
		}

		remaining = matchLen;
		if (closeEm)
		{
			--buffered;
			--remaining;
			addTagPair('EM', emPos, 1, emEndPos, 1);
		}
		if (closeStrong)
		{
			buffered  -= 2;
			remaining -= 2;
			addTagPair('STRONG', strongPos, 2, strongEndPos, 2);
		}

		remaining = Math.min(3, remaining);
		if (remaining & 1)
		{
			emPos = matchPos + matchLen - remaining;
		}
		if (remaining & 2)
		{
			strongPos = matchPos + matchLen - remaining;
		}
		buffered += remaining;
	});
}