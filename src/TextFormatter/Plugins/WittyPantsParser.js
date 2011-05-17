var tags = [],
	tagName  = config.tagName,
	attrName = config.attrName,
	replacements = config.replacements;

matches.singletons.forEach(function(m)
{
	var attrs = {};
	attrs[attrName] = replacements.singletons[m[0][0]];

	tags.push({
		pos   : m[0][1],
		type  : SELF_CLOSING_TAG,
		name  : tagName,
		len   : m[0][0].length,
		attrs : attrs
	});
});

function doQuotation(m)
{
	var attrs = {}, q = m[0][0].substr(-1);
	attrs[attrName] = replacements.quotation[q][0];

	// left character
	tags.push({
		pos   : m[0][1] + m[0][0].indexOf(q),
		type  : SELF_CLOSING_TAG,
		name  : tagName,
		len   : 1,
		attrs : attrs
	});

	var attrs = {};
	attrs[attrName] = replacements.quotation[q][1];

	// right character
	tags.push({
		pos   : m[0][1] + m[0][0].length - 1,
		type  : SELF_CLOSING_TAG,
		name  : tagName,
		len   : 1,
		attrs : attrs,
		requires : [tags.length - 1]
	});
}

matches.quotationSingle.forEach(doQuotation);
matches.quotationDouble.forEach(doQuotation);

matches.symbols.forEach(function(m)
{
	var attrs = {};
	attrs[attrName] = replacements.symbols[m[0][0].toLowerCase()]

	tags.push({
		pos   : m[0][1],
		type  : SELF_CLOSING_TAG,
		name  : tagName,
		len   : m[0][0].length,
		attrs : attrs
	});
});

matches.apostrophe.forEach(function(m)
{
	var attrs = {};
	attrs[attrName] = replacements.apostrophe;

	tags.push({
		// adjust the pos to take the lookbehind-assertions-turned-subpatterns into account
		pos   : m[0][1] + m[0][0].length - 1,
		type  : SELF_CLOSING_TAG,
		name  : tagName,
		len   : 1,
		attrs : attrs
	});
});

/**
* We do "primes" after "apostrophe" so that the character in "80s" gets handled by the
* former rather than the latter
*/
matches.primes.forEach(function(m)
{
	if (replacements.primes[m[0][0]])
	{
		var attrs = {};
		attrs[attrName] = replacements.primes[m[0][0]];

		tags.push({
			pos   : m[0][1],
			type  : SELF_CLOSING_TAG,
			name  : tagName,
			len   : m[0][0].length,
			attrs : attrs
		});
	}
});

matches.multiply.forEach(function(m)
{
	var attrs = {};
	attrs[attrName] = replacements.multiply;

	tags.push({
		pos   : m[1][1],
		type  : SELF_CLOSING_TAG,
		name  : tagName,
		len   : 1,
		attrs : attrs
	});
});

return tags;