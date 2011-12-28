var tags = [];

matches.forEach(function(m)
{
	var tagType = (text[m[0][1] + 1] === '/')
				? END_TAG
				: START_TAG;

	var tagName = config.prefix
				+ ':'
				+ m[1 + (tagType === START_TAG)][0].toLowerCase();

	var attrs = {};

	if (tagType === START_TAG)
	{
		if (m[0][0].substr(m[0][0].length - 2) === '/>')
		{
			tagType = SELF_CLOSING_TAG;
		}

		/**
		* Capture attributes
		*/
		var attrMatch, attrName, attrValue;
		config.attrRegexp.lastIndex = null;

		while (attrMatch = config.attrRegexp.exec(m[3][0]))
		{
			var pos = attrMatch[0].indexOf('=');

			/**
			* Give boolean attributes a value equal to their name, lowercased
			*/
			if (pos === -1)
			{
				pos = attrMatch[0].length;
				attrMatch[0] += '=' + attrMatch[0].toLowerCase();
			}

			attrName  = attrMatch[0].substr(0, pos).replace(/^\s*(.*?)\s*$/, '$1').toLowerCase();
			attrValue = attrMatch[0].substr(1 + pos).replace(/^\s*(.*?)\s*$/, '$1');

			if (/^["']/.test(attrValue))
			{
				 attrValue = attrValue.substr(1, attrValue.length - 2);
			}

			attrs[attrName] = html_entity_decode(attrValue);
		}
	}

	tags.push({
		pos   : m[0][1],
		len   : m[0][0].length,
		name  : tagName,
		type  : tagType,
		attrs : attrs
	});
});

return tags;