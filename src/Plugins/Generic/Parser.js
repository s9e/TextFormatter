.forEach(function(entry)
{
	var tagName = entry[0],
	    regexp  = entry[1],
		map     = entry[2],
		match;

	// Reset the regexp
	regexp.lastIndex = 0;

	while (match = regexp.exec(text))
	{
		var tag = addSelfClosingTag(tagName, match.index, match[0].length);

		map.forEach(function(attrName, i)
		{
			// NOTE: subpatterns with no name have an empty entry to preserve the array indices
			if (attrName)
			{
				tag.setAttribute(attrName, match[i]);
			}
		});
	}
}