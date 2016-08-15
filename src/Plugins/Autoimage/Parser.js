var tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	var tag = addSelfClosingTag(tagName, m[0][1], m[0][0].length, -1);
	tag.setAttribute(attrName, m[0][0]);
});