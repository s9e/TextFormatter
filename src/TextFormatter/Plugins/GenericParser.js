var tags = [];

foreach(matches, function(tagMatches, tagName)
{
	tagMatches.forEach(function(m)
	{
		var attrs = {};

		foreach(config.regexpMap[tagName], function(k, attrName)
		{
			if (attrName)
			{
				attrs[attrName] = m[k][0];
			}
		});

		tags.push({
			pos   : m[0][1],
			name  : tagName,
			type  : SELF_CLOSING_TAG,
			len   : m[0][0].length,
			attrs : attrs
		});
	});
});

return tags;