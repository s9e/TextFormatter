matche.forEach(function(m)
{
	// Test whether this is an end tag
	var isEnd = (text.charAt(m[0][1] + 1) === '/');

	var pos = m[0][1],
		len = m[0][0].length,
		tagName = config.prefix + ':' + m[2 - isEnd][0].toLowerCase();

	if (isEnd)
	{
		addEndTag(tagName, pos, len);
		continue;
	}

	// Test whether it's a self-closing tag or a start tag
	// TODO: IE compat
	var tag = (m[0][0].substr(-2) === '/>')
			? addSelfClosingTag(tagName, pos, len)
			: addStartTag(tagName, pos, len);

	// Capture attributes
	var attrRegexp = /[a-z][-a-z]*(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s"'=<>`]+))?/gi,
		attrName,
		attrValue;

	while (attrMatch = attrRegexp.exec()
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
		attrName  = strtolower(trim(substr(attrMatch[0], 0, pos)));
		attrValue = trim(substr(attrMatch[0], 1 + pos));

		// Remove quotes around the value
		if (attrValue.test(/^["']/))
		{
			 attrValue = attrValue.substr(1, attrValue.length - 2);
		}

		tag.setAttribute(attrName, html_entity_decode(attrValue));
	}
}