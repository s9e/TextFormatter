var tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	var tag = addSelfClosingTag(tagName, m[0][1], m[0][0].length);

	// TODO: revisit loop, get Closure Compiler to optimize the block away if there's no replacements
	if (config.replacements)
	{
		for (var i = 0; i < config.replacements.length; ++i)
		{
			var regexp      = config.replacements[i][0],
				replacement = config.replacements[i][1];

			if (regexp.test(m[0][0]))
			{
				tag.setAttribute(attrName, replacement);
				break;
			}
		}
	}
});