/**
* Parse given inline markup in text
*
* The markup must start and end with exactly 2 characters
*
* @param {string}  str     First markup string
* @param {!RegExp} regexp  Regexp used to match the markup's span
* @param {string}  tagName Name of the tag produced by this markup
*/
function parseInlineMarkup(str, regexp, tagName)
{
	if (text.indexOf(str) === -1)
	{
		return;
	}

	var m;
	while (m = regexp.exec(text))
	{
		var match    = m[0],
			matchPos = m.index,
			matchLen = match.length,
			endPos   = matchPos + matchLen - 2;

		addTagPair(tagName, matchPos, 2, endPos, 2);
		overwrite(matchPos, 2);
		overwrite(endPos, 2);
	}
}