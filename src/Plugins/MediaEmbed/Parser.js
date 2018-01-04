matches.forEach(function(m)
{
	var tagName = config.tagName,
		url     = m[0][0],
		pos     = m[0][1],
		len     = url.length;

	// Give that tag priority over other tags such as Autolink's
	addSelfClosingTag(tagName, pos, len, -10).setAttribute('url', url);
});