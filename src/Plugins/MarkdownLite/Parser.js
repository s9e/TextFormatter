var contentLen, endTagLen, endTagPos, m, match, matchLen, matchPos, regexp, startTag, startTagLen, startTagPos;

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

var hasEscapedChars = (text.indexOf('\\') > -1);

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
	regexp = /`[^\x17`]+`/g;

	while (m = regexp.exec(text))
	{
		matchPos = m['index'];
		matchLen = m[0].length;

		addTagPair('C', matchPos, 1, matchPos + matchLen - 1, 1);

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
