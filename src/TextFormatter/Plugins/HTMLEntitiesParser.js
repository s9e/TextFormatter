var tags = [],
	tagName  = config.tagName,
	attrName = config.attrName,
	b = document.createElement('b'),
	disabled = config.disabled || {};

matches.forEach(function(m)
{
	if (m[0][0] in disabled)
	{
		return;
	}

	b.innerHTML = m[0][0];

	if (b.textContent === m[0][0])
	{
		return;
	}

	var attrs = {};
	attrs[attrName] = b.textContent;

	tags.push({
		pos   : m[0][1],
		type  : SELF_CLOSING_TAG,
		name  : tagName,
		len   : m[0][0].length,
		attrs : attrs
	});
});

return tags;