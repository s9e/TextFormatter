function parse()
{
	var pos = text.indexOf('^');
	if (pos === -1)
	{
		return;
	}

	parseShortForm(pos);
	parseLongForm(pos);
}

/**
* Parse the long form x^(x)
*
* This syntax is supported by RDiscount
*
* @param {number} pos Position of the first relevant character
*/
function parseLongForm(pos)
{
	pos = text.indexOf('^(', pos);
	if (pos === -1)
	{
		return;
	}

	var m, regexp = /\^\([^\x17()]+\)/g;
	regexp.lastIndex = pos;
	while (m = regexp.exec(text))
	{
		var match    = m[0],
			matchPos = +m['index'],
			matchLen = match.length;

		addTagPair('SUP', matchPos, 2, matchPos + matchLen - 1, 1);
		overwrite(matchPos, matchLen);
	}
	if (match)
	{
		parseLongForm(pos);
	}
}

/**
* Parse the short form x^x and x^x^
*
* This syntax is supported by most implementations that support superscript
*
* @param {number} pos Position of the first relevant character
*/
function parseShortForm(pos)
{
	var m, regexp = /\^(?!\()[^\x17\s^()]+\^?/g;
	regexp.lastIndex = pos;
	while (m = regexp.exec(text))
	{
		var match    = m[0],
			matchPos = +m['index'],
			matchLen = match.length,
			startPos = matchPos,
			endLen   = (match.substr(-1) === '^') ? 1 : 0,
			endPos   = matchPos + matchLen - endLen;

		addTagPair('SUP', startPos, 1, endPos, endLen);
	}
}