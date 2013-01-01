var tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	var url = m[0][0];

	// Remove trailing punctuation. We preserve right parentheses if there's a balanced
	// number of parentheses in the URL, e.g.
	//   http://en.wikipedia.org/wiki/Mars_(disambiguation) 
	while (1)
	{
		// We remove some common ASCII punctuation and whitespace. We don't have access to Unicode
		// properties, so we try to cover the most common usage
		url = url.replace(/[\s!"',.<>?]+$/, '');

		// TODO: url.substr(url.length - 1) for IE7
		if (url.substr(-1) === ')'
		 && url.replace(/[^(]+/g, '').length < url.replace(/[^)]+/g, '').length)
		{
			url = url.substr(0, url.length - 1);
			continue;
		}
		break;
	}

	// Create a zero-width start tag right before the URL
	var startTag = addStartTag(tagName, m[0][1], 0);
	startTag.setAttribute(attrName, url);

	// Create a zero-width end tag right after the URL
	var endTag = addEndTag(tagName, m[0][1] + url.length, 0);

	// Pair the tags together
	startTag.pairWith(endTag);
});