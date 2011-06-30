var tags = [];

matches.forEach(function(m)
{
	tags.push({
		pos   : m[0][1],
		name  : 'BR',
		type  : SELF_CLOSING_TAG,
		len   : m[0][0].length
	});
});

return tags;