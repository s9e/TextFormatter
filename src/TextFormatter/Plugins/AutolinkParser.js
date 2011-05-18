var tags = [];

matches.forEach(function(m)
{
	var url = m[0][0];

	/**
	* Remove trailing dots. We preserve right parentheses if there's the right number of
	* parentheses in the URL, as in http://en.wikipedia.org/wiki/Mars_(disambiguation) 
	*/
	while (1)
	{
		url = url.replace(/[^\w\)=\-\/]+$/, '');

		if (url.substr(-1) === ')'
		 && url.replace(/[^\(]+/g, '').length < url.replace(/[^\)]+/g, '').length)
		{
			url = url.substr(0, url.length - 1);
			continue;
		}
		break;
	}

	tags.push({
		pos   : m[0][1],
		name  : 'URL',
		type  : START_TAG,
		len   : 0,
		attrs : { 'url' : url }
	});

	tags.push({
		pos   : m[0][1] + url.length,
		name  : 'URL',
		type  : END_TAG,
		len   : 0
	});
});

return tags;