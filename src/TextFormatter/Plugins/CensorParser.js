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
		for (var mask in config.replacements)
		{
			if (preg_match(mask, m[0][0]))
			{
				tag.attrs = {};
				tag.attrs[attrName] = config.replacements[mask];
				break;
			}
		}
	}

	tags.push(tag);
});

return tags;