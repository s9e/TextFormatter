matches.forEach(function(m)
{
	addSelfClosingTag(config.tagName, m[0][1], m[0][0].length).setAttribute(config.attrName, getSequence(m[0][0]));
});

/**
* Get the sequence of Unicode codepoints that corresponds to given emoji
*
* @param  {!string} str UTF-8 emoji
* @return {!string}     Codepoint sequence, e.g. "23-20e3"
*/
function getSequence(str)
{
	// Remove U+FE0F from the emoji
	str = str.replace(/\uFE0F/g, '');
	var seq = [],
		i   = 0;
	do
	{
		var cp = str.charCodeAt(i);
		if (cp >= 0xD800)
		{
			cp = (cp << 10) + str.charCodeAt(++i) - 56613888;
		}
		seq.push(cp.toString(16));
	}
	while (++i < str.length);

	return seq.join('-');
}