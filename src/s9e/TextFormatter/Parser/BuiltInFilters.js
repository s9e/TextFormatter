/**
* IMPORTANT NOTE: those filters are only meant to catch bad input and honest mistakes. They don't
*                 match their PHP equivalent exactly and may let unwanted values through. Their
*                 result should always be checked by PHP filters
*
* @const
*/
var BuiltInFilters =
{
	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterAlnum: function(attrValue)
	{
		return /^[0-9A-Za-z]+$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterColor: function(attrValue)
	{
		return /^(?:#[0-9a-f]{3,6}|[a-z]+)$/i.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterEmail: function(attrValue)
	{
		return /^[-\w.+]+@[-\w.]+$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterFloat: function(attrValue)
	{
		return /^(?:0|-?[1-9]\d*)(?:\.\d+)?(?:e[1-9]\d*)?$/i.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterIdentifier: function(attrValue)
	{
		return /^[-\w]+$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterInt: function(attrValue)
	{
		return /^(?:0|-?[1-9]\d*)$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterIp: function(attrValue)
	{
		if (/^[\d.]+$/.test(attrValue))
		{
			return BuiltInFilters.filterIpv4(attrValue);
		}

		if (/^[\da-f:]+$/i.test(attrValue))
		{
			return BuiltInFilters.filterIpv6(attrValue);
		}

		return false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterIpport: function(attrValue)
	{
		var m, ip;

		if (m = /^\[([\da-f:]+)(\]:[1-9]\d*)$/i.exec(attrValue))
		{
			ip = BuiltInFilters.filterIpv6(m[1]);

			if (ip === false)
			{
				return false;
			}

			return '[' + ip + m[2];
		}

		if (m = /^([\d.]+)(:[1-9]\d*)$/.exec(attrValue))
		{
			ip = BuiltInFilters.filterIpv4(m[1]);

			if (ip === false)
			{
				return false;
			}

			return ip + m[2];
		}

		return false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterIpv4: function(attrValue)
	{
		if (/^\d+\.\d+\.\d+\.\d+$/.test(attrValue))
		{
			return false;
		}

		var i = 4, p = attrValue.split('.');
		while (--i >= 0)
		{
			// NOTE: ext/filter doesn't support octal notation
			if (p[i].charAt(0) === '0' || p[i] > 255)
			{
				return false;
			}
		}

		return true;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterIpv6: function(attrValue)
	{
		return /^(\d*:){2,7}\d+(?:\.\d+\.\d+\.\d+)?$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @param  {!Array.<Array>}  map
	* @return {*}
	*/
	filterMap: function(attrValue, map)
	{
		var i = -1, cnt = map.length;
		while (++i < cnt)
		{
			if (map[i][0].test(attrValue))
			{
				return map[i][1];
			}
		}

		return attrValue;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterNumber: function(attrValue)
	{
		return /^\d+$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*}       attrValue
	* @param  {!number} min
	* @param  {!number} max
	* @param  {!Logger} logger
	* @return {!number|boolean}
	*/
	filterRange: function(attrValue, min, max, logger)
	{
		if (!/^(?:0|-?[1-9]\d*)$/.test(attrValue))
		{
			return false;
		}

		attrValue = parseInt(attrValue, 10);

		if (attrValue < min)
		{
			logger.warn(
				'Value outside of range, adjusted up to min value',
				{
					'attrValue' : attrValue,
					'min'       : min,
					'max'       : max
				}
			);

			return min;
		}

		if (attrValue > max)
		{
			logger.warn(
				'Value outside of range, adjusted down to max value',
				{
					'attrValue' : attrValue,
					'min'       : min,
					'max'       : max
				}
			);

			return max;
		}

		return attrValue;
	},

	/**
	* @param  {*} attrValue
	* @param  {!RegExp} regexp
	* @return {*}
	*/
	filterRegexp: function(attrValue, regexp)
	{
		return regexp.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterSimpletext: function(attrValue)
	{
		return /^[-\w+., ]+$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @return {*}
	*/
	filterUint: function(attrValue)
	{
		return /^(?:0|[1-9]\d*)$/.test(attrValue) ? attrValue : false;
	},

	/**
	* @param  {*} attrValue
	* @param  {!Object} urlConfig
	* @param  {?} logger
	* @return {*}
	*/
	filterUrl: function(attrValue, urlConfig, logger)
	{
		/**
		* Trim the URL to conform with HTML5
		* @link http://dev.w3.org/html5/spec/links.html#attr-hyperlink-href
		*/
		attrValue = attrValue.replace(/^\s+/, '').replace(/\s+$/, '');

		/**
		* @type {!boolean} Whether to remove the scheme part of the URL
		*/
		var removeScheme = false;

		/**
		* @type {!boolean} Whether to validate the scheme part of the URL
		*/
		var validateScheme = true;

		if (attrValue.substr(0, 2) === '//')
		{
			if (urlConfig.defaultScheme)
			{
				attrValue    = urlConfig.defaultScheme + ':' + attrValue;
				removeScheme = true;
			}
			else if (!urlConfig.requireScheme)
			{
				attrValue      = 'http:' + attrValue;
				removeScheme   = true;
				validateScheme = false;
			}
		}

		// Test whether the URL contains only ASCII characters, and encode them if not
		if (!/^[\u0020-\u007f]+$/.test(attrValue))
		{
			attrValue = BuiltInFilters.encodeUrlToAscii(attrValue);
		}

		// We URL-encode some sensitive characters in case someone would want to use the URL in
		// some JavaScript thingy, or in CSS
		attrValue = attrValue.replace(/['"()<>\r\n]/g, escape).replace(/[\u2028\u2029]/g, encodeURIComponent);

		// Parse the URL... kinda
		var m =/^([a-z\d]+):\/\/(?:[^/]*@)?([^/]+)(?:\/.*)?$/i.exec(attrValue);

		if (!m)
		{
			return false;
		}

		if (validateScheme && !urlConfig.allowedSchemes.test(m[1]))
		{
			logger.err(
				'URL scheme is not allowed',
				{'attrValue': attrValue, 'scheme': m[1]}
			);

			return false;
		}

		if (urlConfig.disallowedHosts)
		{
			if (urlConfig.disallowedHosts.test(m[2]))
			{
				logger.err(
					'URL host is not allowed',
					{'attrValue': attrValue, 'host': m[2]}
				);

				return false;
			}
		}

		// Normalize scheme, or remove if applicable
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
			* 'An implementation should accept uppercase letters as equivalent to lowercase in
			* scheme names (e.g., allow "HTTP" as well as "http") for the sake of robustness but
			* should only produce lowercase scheme names for consistency.'
			*/
			attrValue = attrValue.substr(0, pos).toLowerCase() + attrValue.substr(pos);
		}

		return attrValue;
	},

	/**
	* Encode an UTF-8 URL to ASCII
	*
	* No Punycode encoding in JavaScript, only URL-encoding
	*
	* @param  {!string} url Original URL
	* @return {!string}     Encoded URL
	*/
	encodeUrlToAscii: function(url)
	{
//		if (function_exists('idn_to_ascii')
//		 && preg_match('#^([^:]+://(?:[^/]+@)?)([^/]+)#i', $url, $m))
//		{
//			$url = $m[1] . idn_to_ascii($m[2]) . substr($url, strlen($m[0]));
//		}

		// URL-encode non-ASCII stuff
		return url.replace(/[^\u0020-\u007f]+/g, encodeURIComponent);
	}
}