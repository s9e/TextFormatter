var tagName  = config.tagName,
	attrName = config.attrName;

matches.forEach(function(m)
{
	// NOTE: unlike the PCRE regexp, the Javascript regexp can consume an extra character at the
	//       start of the match, so we have to adjust the position and length accordingly
	var offset = /^\W/.test(m[0][0]) ? 1 : 0,
		word   = m[0][0].substr(offset),
		tag    = addSelfClosingTag(tagName, m[0][1] + offset, word.length);

	// TODO: revisit loop, get Closure Compiler to optimize the block away if there's no replacements
	if (config.replacements)
	{
		for (var i = 0; i < config.replacements.length; ++i)
		{
			var regexp      = config.replacements[i][0],
				replacement = config.replacements[i][1];

			if (regexp.test(word))
			{
				tag.setAttribute(attrName, replacement);
				break;
			}
		}
	}
});