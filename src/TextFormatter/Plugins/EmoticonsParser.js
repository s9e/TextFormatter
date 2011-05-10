var tags = [];

foreach(matches, function(m)
{
	tags.push({
		pos  : m[0][1],
		type : SELF_CLOSING_TAG,
		name : config.tagName,
		len  : m[0][0].length
	});
});

return tags;