matches.forEach(function(m)
{
	addSelfClosingTag(config.tagName, m[0][1], m[0][0].length);
})