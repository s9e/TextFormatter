matches.forEach(function(m)
{
	// Test whether this is an end tag
	var isEnd = (text[m[0][1] + 1] === '/');

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
	var tag = (/(<\S+|['"\s])\/>$/.test(m[0][0]))
			? addTagPair(tagName, pos, len, pos + len, 0)
			: addStartTag(tagName, pos, len);

	captureAttributes(tag, elName, m[3][0]);
});

/**
* Capture all attributes in given string
*
* @param  {!Tag}    tag    Target tag
* @param  {string} elName Name of the HTML element
* @param  {string} str    String containing the attribute declarations
*/
function captureAttributes(tag, elName, str)
{
	var regexp = /([a-z][-a-z0-9]*)(?:\s*=\s*("[^"]*"|'[^']*'|[^\s"'=<>`]+))?/gi,
		attrName,
		attrValue,
		m;

	while (m = regexp.exec(str))
	{
		/**
		* If there's no value, it's a boolean attribute and we generate a value equal
		* to the attribute's name, lowercased
		*
		* @link http://www.w3.org/html/wg/drafts/html/master/single-page.html#boolean-attributes
		*/
		attrName  = m[1].toLowerCase();
		attrValue = (typeof m[2] !== 'undefined') ? m[2] : attrName;

		// Use the attribute's alias if applicable
		if (HINT.HTMLELEMENTS_HAS_ALIASES && config.aliases && config.aliases[elName] && config.aliases[elName][attrName])
		{
			attrName = config.aliases[elName][attrName];
		}

		// Remove quotes around the value
		if (/^["']/.test(attrValue))
		{
			attrValue = attrValue.substring(1, attrValue.length - 1);
		}

		tag.setAttribute(attrName, html_entity_decode(attrValue));
	}
}