matches.forEach(function(m)
{
	var url      = m[0][0],
		startPos = m[0][1],
		endPos   = startPos + url.length;

	// Remove trailing punctuation. We preserve right parentheses if there's a balanced
	// number of parentheses in the URL, e.g.
	//   http://en.wikipedia.org/wiki/Mars_(disambiguation) 
	while (1)
	{
		url = url.replace(/[^-\w)=\/]+$/, '');

		if (url.substr(url.length - 1) === ')'
		 && url.replace(/[^(]+/g, '').length < url.replace(/[^)]+/g, '').length)
		{
			url = url.substr(0, url.length - 1);
			continue;
		}
		break;
	}

	addStartTag(config.tagName, startPos, 0).setAttribute(config.attrName, url);
	addEndTag(config.tagName, endPos, 0);
});