matches.forEach(function(m)
{
	// Test whether this is an end tag
	var isEnd = (text.charAt(m[0][1] + 1) === '/');

	var pos    = m[0][1],
		len    = m[0][0].length,
		elName = m[2 - isEnd][0].toLowerCase();

	// Use the element's alias if applicable, or the  name of the element (with the
	// configured prefix) otherwise
	var tagName = (config.aliases && config.aliases[elName] && config.aliases[elName][''])
	            ? config.aliases[elName]['']
	            : config.prefix + ':' + elName;

	if (isEnd)
	{
		addEndTag(tagName, pos, len);

		return;
	}

	// Test whether it's a self-closing tag or a start tag.
	//
	// A self-closing tag will become one start tag consuming all of the text followed by a
	// 0-width end tag. Alternatively, it could be replaced by a pair of 0-width tags plus
	// an ignore tag to prevent the text in between from being output
	var tag = (m[0][0].substr(len - 2) === '/>')
			? addTagPair(tagName, pos, len, pos + len, 0)
			: addStartTag(tagName, pos, len);

	// Capture attributes
	var attrRegexp = /[a-z][-a-z]*(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s"'=<>`]+))?/gi,
		attrName,
		attrValue,
		attrMatch;

	while (attrMatch = attrRegexp.exec(m[3][0]))
	{
		pos = attrMatch[0].indexOf('=');

		/**
		* If there's no equal sign, it's a boolean attribute and we generate a value equal
		* to the attribute's name, lowercased
		*
		* @link http://www.w3.org/html/wg/drafts/html/master/single-page.html#boolean-attributes
		*/
		if (pos < 0)
		{
			pos = attrMatch[0].length;
			attrMatch[0] += '=' + attrMatch[0].toLowerCase();
		}

		// Normalize the attribute name, remove the whitespace around its value to account
		// for cases like <b title = "foo"/>
		attrName  = attrMatch[0].substr(0, pos).toLowerCase().replace(/^\s+/, '').replace('/\s+$/', '');
		attrValue = attrMatch[0].substr(1 + pos).replace(/^\s+/, '').replace('/\s+$/', '');

		// Use the attribute's alias if applicable
		if (config.aliases && config.aliases[elName] && config.aliases[elName][attrName])
		{
			attrName = config.aliases[elName][attrName];
		}

		// Remove quotes around the value
		if (/^["']/.test(attrValue))
		{
			attrValue = attrValue.substr(1, attrValue.length - 2);
		}

		tag.setAttribute(attrName, html_entity_decode(attrValue));
	}
});