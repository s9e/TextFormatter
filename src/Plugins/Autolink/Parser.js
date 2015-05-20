var tagName  = config.tagName,
	attrName = config.attrName,
	chars    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

matches.forEach(function(m)
{
	var url    = m[0][0],
		tagPos = m[0][1];

	// Make sure that the URL is not preceded by an alphanumeric character
	if (tagPos > 0 && chars.indexOf(text.charAt(tagPos - 1)) > -1)
	{
		return;
	}

	// Remove trailing punctuation and right angle brackets. We preserve right parentheses
	// if there's a balanced number of parentheses in the URL, e.g.
	//   http://en.wikipedia.org/wiki/Mars_(disambiguation)
	while (1)
	{
		// We remove some common ASCII punctuation and whitespace. We don't have access to Unicode
		// properties, so we try to cover the most common usage
		url = url.replace(/[\s!"',.<>?]+$/, '');

		if (url.substr(-1) === ')' && url.replace(/[^(]+/g, '').length < url.replace(/[^)]+/g, '').length)
		{
			url = url.substr(0, url.length - 1);
			continue;
		}
		break;
	}

	// Create a zero-width end tag right after the URL
	var endTag = addEndTag(tagName, tagPos + url.length, 0);

	// If the URL starts with "www." we prepend "http://"
	if (url.charAt(3) === '.')
	{
		url = 'http://' + url;
	}

	// Create a zero-width start tag right before the URL
	var startTag = addStartTag(tagName, tagPos, 0);
	startTag.setAttribute(attrName, url);

	// Pair the tags together
	startTag.pairWith(endTag);
});