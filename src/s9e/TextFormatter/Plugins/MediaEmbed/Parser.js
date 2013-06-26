matches.forEach(function(m)
{
	var url = m[0][0],
		pos = m[0][1],
		len = url.length;

	addSelfClosingTag('MEDIA', pos, len).setAttribute('url', url);
});