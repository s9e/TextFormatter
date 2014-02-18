var contentLen, endTagLen, endTagPos, hasEscapedChars, m, match, matchLen, matchPos, regexp, startTag, startTagLen, startTagPos, tag, tagLen, title, url;

if (text.indexOf('\\') < 0)
{
	hasEscapedChars = false;
}
else
{
	hasEscapedChars = true;

	// Encode escaped literals that have a special meaning otherwise, so that we don't have
	// to take them into account in regexps
	text = text.replace(
		/\\[!")*[\\\]^_`~]/g,
		function (str)
		{
			return {
				'\\!'  : "\x1B0",
				'\\"'  : "\x1B1",
				'\\)'  : "\x1B2",
				'\\*'  : "\x1B3",
				'\\['  : "\x1B4",
				'\\\\' : "\x1B5",
				'\\]'  : "\x1B6",
				'\\^'  : "\x1B7",
				'\\_'  : "\x1B8",
				'\\`'  : "\x1B9",
				'\\~'  : "\x1BA"
			}[str];
		}
	);
}

// We append a couple of lines and a non-whitespace character at the end of the text in
// order to trigger the closure of all open blocks such as quotes and lists
text += "\n\n\x17";

var boundaries   = [],
	codeIndent   = 4,
	codeTag,
	lineIsEmpty  = true,
	lists        = [],
	listsCnt     = 0,
	newContext   = false,
	quotes       = [],
	quotesCnt    = 0,
	setextLines  = {},
	textBoundary = 0,
	breakParagraph,
	continuation,
	endTag,
	ignoreLen,
	lfPos,
	listIndex,
	maxIndent,
	minIndent,
	quoteDepth;

// Capture the underlines used for Setext-style headers
if (text.indexOf("-\n") || text.indexOf("=\n"))
{
	// Capture the any series of - or = alone on a line, optionally preceded with the
	// angle brackets notation used in blockquotes
	regexp = /^(?=[-=>])(?:> ?)*(?=[-=])(?:-+|=+)$/gm;

	while (m = regexp.exec(text))
	{
		match    = m[0];
		matchPos = m['index'];

		// Compute the position of the end tag. We start on the LF character before the
		// match and keep rewinding until we find a non-space character
		endTagPos = matchPos - 1;
		while (endTagPos > 0 && text[endTagPos - 1] === ' ')
		{
			--endTagPos;
		}

		// Store at the offset of the LF character
		setextLines[matchPos - 1] = {
			endTagLen  : matchPos + match.length - endTagPos,
			endTagPos  : endTagPos,
			quoteDepth : match.length - match.replace(/>/g, '').length,
			tagName    : ((match.charAt(0)) === '=') ? 'H1' : 'H2'
		};
	}
}

regexp = /^(?:(?=[-*+\d \t>`#_])((?: {0,3}> ?)+)?([ \t]+)?(\* *\* *\*[* ]*$|- *- *-[- ]*$|_ *_ *_[_ ]*$)?((?:[-*+]|\d+\.)[ \t]+(?=.))?[ \t]*(#+[ \t]*(?=.)|```+)?)?/gm;

while (m = regexp.exec(text))
{
	matchPos  = m['index'];
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
			tag = addStartTag('QUOTE', matchPos, 0);
			tag.setSortPriority(quotesCnt);

			quotes.push(tag);
		}
		while (quoteDepth > ++quotesCnt);
	}

	// Compute the width of the indentation
	var indentWidth = 0,
		indentPos   = 0,
		indentStr,
		indentLen;
	if (m[2])
	{
		indentStr = m[2];
		indentLen = indentStr.length;

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
		while (++indentPos < indentLen && indentWidth < codeIndent);
	}

	// Test whether we're out of a code block
	if (indentWidth < codeIndent && codeTag && !lineIsEmpty)
	{
		newContext = true;
	}

	if (newContext)
	{
		newContext = false;

		// Close the code block if applicable
		if (codeTag)
		{
			endTag = addEndTag('CODE', textBoundary, 0);
			endTag.pairWith(codeTag);
			endTag.setSortPriority(-1);
			codeTag = null;
		}

		// Close all the lists
		lists.forEach(function(list)
		{
			addEndTag('LIST', textBoundary, 0).pairWith(list.listTag);
			addEndTag('LI',   textBoundary, 0).pairWith(list.itemTag);
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
			list = lists.pop();
			--listsCnt;

			addEndTag('LIST', textBoundary, 0).pairWith(list.listTag);
			addEndTag('LI',   textBoundary, 0).pairWith(list.itemTag);
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
			var tagPos = matchPos + ignoreLen + indentPos,
				tagLen = m[4].length;

			// Create a LI tag that consumes its markup
			var itemTag = addStartTag('LI', tagPos, tagLen);
			itemTag.removeFlags(RULE_CREATE_PARAGRAPHS);

			// Overwrite the markup
			overwrite(tagPos, tagLen);

			// If the list index is within current lists count it means this is not a new
			// list and we have to close the last item. Otherwise, it's a new list that we
			// have to create
			if (listIndex < listsCnt)
			{
				addEndTag('LI', textBoundary, 0).pairWith(lists[listIndex].itemTag);
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
					minIndent : minIndent,
					maxIndent : maxIndent
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
			endTagPos   = lfPos;
			endTagLen   = 0;

			// Consume the leftmost whitespace and # characters as part of the end tag
			while (" #\t".indexOf(text.charAt(endTagPos - 1)) > -1)
			{
				--endTagPos;
				++endTagLen;
			}

			addTagPair('H' + /#{1,6}/.exec(m[5])[0].length, startTagPos, startTagLen, endTagPos, endTagLen);

			// Mark the start and the end of the header as boundaries
			boundaries.push(startTagPos);
			boundaries.push(endTagPos);

			if (continuation)
			{
				breakParagraph = true;
			}
		}
	}
	else if (m[3] && !listsCnt)
	{
		// Horizontal rule
		addSelfClosingTag('HR', matchPos + ignoreLen, matchLen - ignoreLen);
		breakParagraph = true;
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
}

boundaries.forEach(function(pos)
{
	text = text.substr(0, pos) + "\x17" + text.substr(1 + pos);
});

// Inline code
if (text.indexOf('`') > -1)
{
	regexp = /(``?)[^\x17]*?[^`]\1(?!`)/g;

	while (m = regexp.exec(text))
	{
		matchPos = m['index'];
		matchLen = m[0].length;
		tagLen   = m[1].length;

		addTagPair('C', matchPos, tagLen, matchPos + matchLen - tagLen, tagLen);

		// Overwrite the markup
		overwrite(matchPos, matchLen);
	}
}

// Images
if (text.indexOf('![') > -1)
{
	regexp = /!\[([^\x17\]]+)] ?\(([^\x17 ")]+)(?: "([^\x17"]*)")?\)/g;

	while (m = regexp.exec(text))
	{
		matchPos    = m['index'];
		matchLen    = m[0].length;
		contentLen  = m[1].length;
		startTagPos = matchPos;
		startTagLen = 2;
		endTagPos   = startTagPos + startTagLen + contentLen;
		endTagLen   = matchLen - startTagLen - contentLen;

		startTag = addTagPair('IMG', startTagPos, startTagLen, endTagPos, endTagLen);
		startTag.setAttribute('alt', decode(m[1], hasEscapedChars));
		startTag.setAttribute('src', decode(m[2], hasEscapedChars));

		if (m[3] > '')
		{
			startTag.setAttribute('title', decode(m[3], hasEscapedChars));
		}

		// Overwrite the markup
		overwrite(matchPos, matchLen);
	}
}

// Inline links
if (text.indexOf('[') > -1)
{
	regexp = /\[([^\x17\]]+)] ?\(([^\x17)]+)\)/g;

	while (m = regexp.exec(text))
	{
		matchPos    = m['index'];
		matchLen    = m[0].length;
		contentLen  = m[1].length;
		startTagPos = matchPos;
		startTagLen = 1;
		endTagPos   = startTagPos + startTagLen + contentLen;
		endTagLen   = matchLen - startTagLen - contentLen;

		// Split the URL from the title if applicable
		url   = m[2];
		title = '';
		if (m = /^(.+?) "(.*?)"$/.exec(url))
		{
			url   = m[1];
			title = m[2];
		}

		tag = addTagPair('URL', startTagPos, startTagLen, endTagPos, endTagLen);
		tag.setAttribute('url', decode(url, hasEscapedChars));

		if (title !== '')
		{
			tag.setAttribute('title', decode(title, hasEscapedChars));
		}

		// Overwrite the markup without touching the link's text
		overwrite(startTagPos, startTagLen);
		overwrite(endTagPos,   endTagLen);
	}
}

// Strikethrough
if (text.indexOf('~~') > -1)
{
	regexp = /~~[^\x17]+?~~/g;

	while (m = regexp.exec(text))
	{
		match    = m[0];
		matchPos = m['index'];
		matchLen = match.length;

		addTagPair('DEL', matchPos, 2, matchPos + matchLen - 2, 2);
	}
}

// Superscript
if (text.indexOf('^') > -1)
{
	regexp = /\^[^\x17\s]+/g;

	while (m = regexp.exec(text))
	{
		match       = m[0];
		matchPos    = m['index'];
		matchLen    = match.length;
		startTagPos = matchPos;
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

// Emphasis
[['*', /\*+/g], ['_', /_+/g]].forEach(function(args)
{
	var c = args[0], regexp = args[1];

	if (text.indexOf(c) < 0)
	{
		return;
	}

	var buffered = 0,
		breakPos = text.indexOf("\x17"),
		emPos,
		emEndPos,
		strongPos,
		strongEndPos;

	while (m = regexp.exec(text))
	{
		match     = m[0];
		matchPos  = m['index'];
		matchLen  = match.length;

		// Test whether we've just passed the limits of a block
		if (matchPos > breakPos)
		{
			// Reset the buffer then look for the next break
			buffered = 0;
			breakPos = text.indexOf("\x17", matchPos);
		}

		if (matchLen >= 3)
		{
			// Number of characters left unconsumed
			var remaining = matchLen;

			if (buffered < 3)
			{
				strongEndPos = emEndPos = matchPos;
			}
			else
			{
				// Determine the order of strong's and em's end tags
				if (emPos < strongPos)
				{
					// If em starts before strong, it must end after it
					strongEndPos = matchPos;
					emEndPos     = matchPos + 2;
				}
				else
				{
					// Make strong end after em
					strongEndPos = matchPos + 1;
					emEndPos     = matchPos;

					// If the buffer holds three consecutive characters and the order of
					// strong and em is not defined we push em inside of strong
					if (strongPos === emPos)
					{
						emPos += 2;
					}
				}
			}

			// 2 or 3 means a strong is buffered
			// Strong uses the outer characters
			if (buffered & 2)
			{
				addTagPair('STRONG', strongPos, 2, strongEndPos, 2);
				remaining -= 2;
			}

			// 1 or 3 means an em is buffered
			// Em uses the inner characters
			if (buffered & 1)
			{
				addTagPair('EM', emPos, 1, emEndPos, 1);
				--remaining;
			}

			if (!remaining)
			{
				buffered = 0;
			}
			else
			{
				buffered = Math.min(remaining, 3);

				if (buffered & 1)
				{
					emPos = matchPos + matchLen - buffered; 
				}

				if (buffered & 2)
				{
					strongPos = matchPos + matchLen - buffered; 
				}
			}
		}
		else if (matchLen === 2)
		{
			if (buffered === 3 && strongPos === emPos)
			{
				addTagPair('STRONG', emPos + 1, 2, matchPos, 2);
				buffered = 1;
			}
			else if (buffered & 2)
			{
				addTagPair('STRONG', strongPos, 2, matchPos, 2);
				buffered -= 2;
			}
			else
			{
				buffered += 2;
				strongPos = matchPos;
			}
		}
		else
		{
			// Ignore single underscores when they are between alphanumeric ASCII chars
			if (c === '_'
			 && matchPos > 0
			 && ' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'.indexOf(text.charAt(matchPos - 1)) > 0
			 && ' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'.indexOf(text.charAt(matchPos + 1)) > 0)
			{
				 continue;
			}

			if (buffered === 3 && strongPos === emPos)
			{
				addTagPair('EM', strongPos + 2, 1, matchPos, 1);
				buffered = 2;
			}
			else if (buffered & 1)
			{
				addTagPair('EM', emPos, 1, matchPos, 1);
				--buffered;
			}
			else
			{
				++buffered;
				emPos = matchPos;
			}
		}
	}
});

/**
* Decode a chunk of encoded text to be used as an attribute value
*
* Decodes escaped literals and removes slashes and 0x1A characters
*
* @param  {!string}  str      Encoded text
* @param  {!boolean} unescape Whether to unescape 0x1B sequences
* @return {!string}           Decoded text
*/
function decode(str, unescape)
{
	return str.replace(/[\\\x1A]/g, '').replace(
		/\x1B./g,
		function (str)
		{
			return {
				"\x1B0" : '!',
				"\x1B1" : '"',
				"\x1B2" : ')',
				"\x1B3" : '*',
				"\x1B4" : '[',
				"\x1B5" : '\\',
				"\x1B6" : ']',
				"\x1B7" : '^',
				"\x1B8" : '_',
				"\x1B9" : '`',
				"\x1BA" : '~'
			}[str];
		}
	);
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
