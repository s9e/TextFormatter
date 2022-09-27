matches.forEach((m) =>
{
	// Linkify the trimmed URL
	linkifyUrl(m[0][1], trimUrl(m[0][0]));
});

/**
* Linkify given URL at given position
*
* @param {number} tagPos URL's position in the text
* @param {string} url    URL
*/
function linkifyUrl(tagPos, url)
{
	// Create a zero-width end tag right after the URL
	let endPos = tagPos + url.length,
		endTag = addEndTag(config.tagName, endPos, 0);

	// If the URL starts with "www." we prepend "http://"
	if (url[3] === '.')
	{
		url = 'http://' + url;
	}

	// Create a zero-width start tag right before the URL, with a slightly worse priority to
	// allow specialized plugins to use the URL instead
	let startTag = addStartTag(config.tagName, tagPos, 0, 1);
	startTag.setAttribute(config.attrName, url);

	// Pair the tags together
	startTag.pairWith(endTag);

	// Protect the tag's content from partial replacements with a low priority tag
	let contentTag = addVerbatim(tagPos, endPos - tagPos, 1000);
	startTag.cascadeInvalidationTo(contentTag);
}

/**
* Remove trailing punctuation from given URL
*
* We remove most ASCII non-letters from the end of the string.
* Exceptions:
*  - dashes and underscores, (base64 IDs could end with one)
*  - equal signs, (because of "foo?bar=")
*  - plus signs, (used by some file share services to force download)
*  - trailing slashes,
*  - closing parentheses. (they are balanced separately)
*
* @param  {string} url Original URL
* @return {string}     Trimmed URL
*/
function trimUrl(url)
{
	return url.replace(/(?:(?![-=+)\/_])[\s!-.:-@[-`{-~])+$/, '');
}