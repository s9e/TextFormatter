function parse()
{
	if (text.indexOf('^') === -1)
	{
		return;
	}

	var m, regexp = /\^[^\x17\s]+/g;
	while (m = regexp.exec(text))
	{
		var match    = m[0],
			matchPos = m['index'],
			matchLen = match.length,
			startPos = matchPos,
			endPos   = matchPos + matchLen;

		var parts = match.split('^');
		parts.shift();

		parts.forEach(function(part)
		{
			addTagPair('SUP', startPos, 1, endPos, 0);
			startPos += 1 + part.length;
		});
	}
}