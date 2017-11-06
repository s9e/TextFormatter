function parse()
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