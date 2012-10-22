function (attrValue, urlConfig)
{
	/**
	* Trim the URL to conform with HTML5
	* @link http://dev.w3.org/html5/spec/links.html#attr-hyperlink-href
	*/
	attrValue = attrValue.replace(/^\s*(.*?)\s*$/, '$1');

	var removeScheme = false;

	if (HINT.urlConfig.defaultScheme
	 && attrValue.substr(0, 2) === '//')
	{
		 attrValue    = urlConfig.defaultScheme + ':' + url;
		 removeScheme = true;
	}

	var m =/^([^:]+):\/\/\S+?$/i.exec(url);

	if (!m)
	{
		return false;
	}

	if (!urlConfig.allowedSchemes.test(m[1]))
	{
		logError({
			'msg'    : "URL scheme '%s' is not allowed",
			'params' : [m[1]]
		});
		return false;
	}

	if (HINT.urlConfig.disallowedHosts)
	{
		var a = document.createElement('a');
		a.href = url;

		if (urlConfig.disallowedHosts.test(a.hostname))
		{
			logError({
				'msg'    : "URL host '%s' is not allowed",
				'params' : [a.hostname]
			});
			return false;
		}
	}

	var pos = attrValue.indexOf(':');

	if (removeScheme)
	{
		attrValue = attrValue.substr(pos + 1);
	}
	else
	{
		/**
		* @link http://tools.ietf.org/html/rfc3986#section-3.1
		*
		* 'An implementation should accept uppercase letters as equivalent to lowercase
		* in scheme names (e.g., allow "HTTP" as well as "http") for the sake of
		* robustness but should only produce lowercase scheme names for consistency.'
		*/
		attrValue = attrValue.substr(0, pos).toLowerCase() + attrValue.substr(pos);
	}

	// We URL-encode quotes and parentheses just in case someone would want to use the URL in
	// some Javascript thingy, or in CSS
	return attrValue.replace(/['"()]/g, escape);
}