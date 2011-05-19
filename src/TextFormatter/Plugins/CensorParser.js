var tags = [],
	tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	var tag = {
		pos  : m[0][1],
		name : tagName,
		type : SELF_CLOSING_TAG,
		len  : m[0][0].length
	};

	if (config.replacements)
	{
		var i = 0,
			cnt = config.replacements.length;

		do
		{
			if (config.replacements[i][0].test(m[0][0]))
			{
				tag.attrs = {};
				tag.attrs[attrName] = config.replacements[i][1];
				break;
			}
		}
		while (++i < cnt);
	}

	tags.push(tag);
});

return tags;