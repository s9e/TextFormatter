const regexps  = config.regexps,
      tagName  = config.tagName,
      attrName = config.attrName;

let onlyFirst = typeof config.onlyFirst !== 'undefined',
	keywords  = {};

regexps.forEach((regexp) =>
{
	let m;

	regexp.lastIndex = 0;
	while (m = regexp.exec(text))
	{
		let value = m[0],
			pos   = m.index;

		if (onlyFirst)
		{
			if (value in keywords)
			{
				continue;
			}

			keywords[value] = 1;
		}

		addSelfClosingTag(tagName, pos, value.length).setAttribute(attrName, value);
	}
});