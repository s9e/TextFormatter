var contentLen, endTagLen, endTagPos, m, match, matchLen, matchPos, regexp, startTag, startTagLen, startTagPos, tagLen,
	hasEscapedChars = (text.indexOf('\\') > -1);

// Encode escaped literals that have a special meaning otherwise, so that we don't have to
// take them into account in regexps
if (hasEscapedChars)
{
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

		addTagPair('URL', startTagPos, startTagLen, endTagPos, endTagLen)
			.setAttribute('url', decode(m[2], hasEscapedChars));
	}

	// Overwrite the markup without touching the link's text
	overwrite(startTagPos, startTagLen);
	overwrite(endTagPos,   endTagLen);
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
		if (breakPos > -1 && matchPos > breakPos)
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
			 && matchPos < textLen - 1
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
