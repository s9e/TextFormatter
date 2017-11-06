function parse()
{
	parseEmphasisByCharacter('*', /\*+/g);
	parseEmphasisByCharacter('_', /_+/g);
}

/**
* Get emphasis markup split by block
*
* @param  {!RegExp} regexp Regexp used to match emphasis
* @param  {number}  pos    Position in the text of the first emphasis character
* @return {!Array}         Each array contains a list of [matchPos, matchLen] pairs
*/
function getEmphasisByBlock(regexp, pos)
{
	var block    = [],
		blocks   = [],
		breakPos = text.indexOf("\x17", pos),
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
* Test whether emphasis should be ignored at the given position in the text
*
* @param  {number}  pos Position of the emphasis in the text
* @param  {number}  len Length of the emphasis
* @return {boolean}
*/
function ignoreEmphasis(pos, len)
{
	// Ignore single underscores between alphanumeric characters
	return (text.charAt(pos) === '_' && len === 1 && isSurroundedByAlnum(pos, len));
}

/**
* Parse emphasis and strong applied using given character
*
* @param  {string} character Markup character, either * or _
* @param  {!RegExp} regexp    Regexp used to match the series of emphasis character
*/
function parseEmphasisByCharacter(character, regexp)
{
	var pos = text.indexOf(character);
	if (pos === -1)
	{
		return;
	}

	getEmphasisByBlock(regexp, pos).forEach(processEmphasisBlock);
}


/**
* Process a list of emphasis markup strings
*
* @param {!Array<!Array<!number>>} block List of [matchPos, matchLen] pairs
*/
function processEmphasisBlock(block)
{
	var emPos     = null,
		strongPos = null;

	block.forEach(function(pair)
	{
		var matchPos     = pair[0],
			matchLen     = pair[1],
			canOpen      = !isBeforeWhitespace(matchPos + matchLen - 1),
			canClose     = !isAfterWhitespace(matchPos),
			closeLen     = (canClose) ? Math.min(matchLen, 3) : 0,
			closeEm      = (closeLen & 1) && emPos     !== null,
			closeStrong  = (closeLen & 2) && strongPos !== null,
			emEndPos     = matchPos,
			strongEndPos = matchPos,
			remaining    = matchLen;

		if (emPos !== null && emPos === strongPos)
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

		if (closeEm)
		{
			--remaining;
			addTagPair('EM', emPos, 1, emEndPos, 1);
			emPos = null;
		}
		if (closeStrong)
		{
			remaining -= 2;
			addTagPair('STRONG', strongPos, 2, strongEndPos, 2);
			strongPos = null;
		}

		if (canOpen)
		{
			remaining = Math.min(remaining, 3);
			if (remaining & 1)
			{
				emPos     = matchPos + matchLen - remaining;
			}
			if (remaining & 2)
			{
				strongPos = matchPos + matchLen - remaining;
			}
		}
	});
}