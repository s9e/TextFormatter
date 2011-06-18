var tags = [];

matches.forEach(function(m)
{
	tags.push({
		pos  : m[0][1],
		type : START_TAG,
		name : config.tagName,
		len  : 0
	});
});

return tags;