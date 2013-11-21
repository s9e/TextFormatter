var regexps  = config.regexps,
	tagName  = config.tagName,
	attrName = config.attrName;

regexps.forEach(function(regexp)
{
	var m;

	regexp.lastIndex = 0;
	while (m = regexp.exec(text))
	{
		// NOTE: coercing m.index to a number because Closure Compiler thinks pos is a string otherwise
		var value = m[0],
			pos   = +m['index'];

		addSelfClosingTag(tagName, pos, value.length).setAttribute(attrName, value);
	}
});