var tags = [],
	tagName  = config.tagName,
	attrName = config.attrName,
	disabled = config.disabled || {};

matches.forEach(function(m)
{
	if (m[0][0] in disabled)
	{
		return;
	}

	var chr = html_entity_decode(m[0][0]);

	if (chr === m[0][0])
	{
		return;
	}

	var attrs = {};
	attrs[attrName] = chr;

	tags.push({
		pos   : m[0][1],
		type  : SELF_CLOSING_TAG,
		name  : tagName,
		len   : m[0][0].length,
		attrs : attrs
	});
});

return tags;