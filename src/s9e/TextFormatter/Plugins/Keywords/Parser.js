var regexps  = config.regexps,
	tagName  = config.tagName,
	attrName = config.attrName;

regexps.forEach(function(regexp)
{
	var m;

	regexp.lastIndex = 0;
	while (m = regexp.exec(text))
	{
		// NOTE: using parseInt() here because Closure Compiler thinks pos is a string otherwise
		var value = m[0],
			pos = parseInt(m['index'], 10),
			len = value.length;

		if (config.map && (value in config.map))
		{
			value = config.map[value];
		}

		addSelfClosingTag(tagName, pos, len).setAttribute(attrName, value);
	}
});