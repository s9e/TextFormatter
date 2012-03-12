var tags = [],
	tagName = config.tagName;

matches.forEach(function(m)
{
	tags.push({
		pos  : m[0][1],
		type : SELF_CLOSING_TAG,
		name : tagName,
		len  : m[0][0].length
	});
});

return tags;