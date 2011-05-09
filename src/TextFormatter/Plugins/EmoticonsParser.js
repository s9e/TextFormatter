foreach(matches, function(m)
{
	unprocessedTags.push({
		pos  : m[0][1],
		type : SELF_CLOSING_TAG,
		name : config.tagName,
		len  : m[0][0].length
	});
});