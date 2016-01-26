<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

class BuiltInFilters
{
	/**
	* Filter an alphanumeric value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterAlnum($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_REGEXP, [
			'options' => ['regexp' => '/^[0-9A-Za-z]+$/D']
		]);
	}

	/**
	* Filter a color value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterColor($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_REGEXP, [
			'options' => [
				'regexp' => '/^(?>#[0-9a-f]{3,6}|rgb\\(\\d{1,3}, *\\d{1,3}, *\\d{1,3}\\)|[a-z]+)$/Di'
			]
		]);
	}

	/**
	* Filter an email value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterEmail($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_EMAIL);
	}

	/**
	* Invalidate an attribute value
	*
	* @param  string $attrValue Original value
	* @return bool              Always FALSE
	*/
	public static function filterFalse($attrValue)
	{
		return false;
	}

	/**
	* Filter a float value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterFloat($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_FLOAT);
	}

	/**
	* Filter a value through a hash map
	*
	* @param  string $attrValue Original value
	* @param  array  $map       Associative array
	* @param  bool   $strict    Whether this map is strict (values with no match are invalid)
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterHashmap($attrValue, array $map, $strict)
	{
		if (isset($map[$attrValue]))
		{
			return $map[$attrValue];
		}

		return ($strict) ? false : $attrValue;
	}

	/**
	* Filter an identifier value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterIdentifier($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_REGEXP, [
			'options' => ['regexp' => '/^[-0-9A-Za-z_]+$/D']
		]);
	}

	/**
	* Filter an int value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterInt($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_INT);
	}

	/**
	* Filter an IP value (includes IPv4 and IPv6)
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterIp($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_IP);
	}

	/**
	* Filter an IP:port value (includes IPv4 and IPv6)
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterIpport($attrValue)
	{
		if (preg_match('/^\\[([^\\]]+)(\\]:[1-9][0-9]*)$/D', $attrValue, $m))
		{
			$ip = self::filterIpv6($m[1]);

			if ($ip === false)
			{
				return false;
			}

			return '[' . $ip . $m[2];
		}

		if (preg_match('/^([^:]+)(:[1-9][0-9]*)$/D', $attrValue, $m))
		{
			$ip = self::filterIpv4($m[1]);

			if ($ip === false)
			{
				return false;
			}

			return $ip . $m[2];
		}

		return false;
	}

	/**
	* Filter an IPv4 value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterIpv4($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}

	/**
	* Filter an IPv6 value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterIpv6($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}

	/**
	* Filter a mapped value
	*
	* NOTE: if there's no match, the original value is returned
	*
	* @param  string $attrValue Original value
	* @param  array  $map       List in the form [[<regexp>, <value>]]
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterMap($attrValue, array $map)
	{
		foreach ($map as $pair)
		{
			if (preg_match($pair[0], $attrValue))
			{
				return $pair[1];
			}
		}

		return $attrValue;
	}

	/**
	* Filter a numbervalue
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterNumber($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_REGEXP, [
			'options' => ['regexp' => '/^[0-9]+$/D']
		]);
	}

	/**
	* Filter a range value
	*
	* @param  string  $attrValue Original value
	* @param  integer $min       Minimum value
	* @param  integer $max       Maximum value
	* @param  Logger  $logger    Parser's Logger instance
	* @return mixed              Filtered value, or FALSE if invalid
	*/
	public static function filterRange($attrValue, $min, $max, Logger $logger = null)
	{
		$attrValue = filter_var($attrValue, FILTER_VALIDATE_INT);

		if ($attrValue === false)
		{
			return false;
		}

		if ($attrValue < $min)
		{
			if (isset($logger))
			{
				$logger->warn(
					'Value outside of range, adjusted up to min value',
					[
						'attrValue' => $attrValue,
						'min'       => $min,
						'max'       => $max
					]
				);
			}

			return $min;
		}

		if ($attrValue > $max)
		{
			if (isset($logger))
			{
				$logger->warn(
					'Value outside of range, adjusted down to max value',
					[
						'attrValue' => $attrValue,
						'min'       => $min,
						'max'       => $max
					]
				);
			}

			return $max;
		}

		return $attrValue;
	}

	/**
	* Filter a value by regexp
	*
	* @param  string $attrValue Original value
	* @param  string $regexp    Filtering regexp
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterRegexp($attrValue, $regexp)
	{
		return filter_var($attrValue, FILTER_VALIDATE_REGEXP, [
			'options' => ['regexp' => $regexp]
		]);
	}

	/**
	* Filter a simpletext value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterSimpletext($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_REGEXP, [
			'options' => ['regexp' => '/^[- +,.0-9A-Za-z_]+$/D']
		]);
	}

	/**
	* Filter a uint value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterUint($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_INT, [
			'options' => ['min_range' => 0]
		]);
	}

	/**
	* Filter an URL
	*
	* @param  mixed  $attrValue Original URL
	* @param  array  $urlConfig URL config
	* @param  Logger $logger    Parser's logger
	* @return mixed             Cleaned up URL if valid, FALSE otherwise
	*/
	public static function filterUrl($attrValue, array $urlConfig, Logger $logger = null)
	{
		/**
		* Trim the URL to conform with HTML5 then parse it
		* @link http://dev.w3.org/html5/spec/links.html#attr-hyperlink-href
		*/
		$p = self::parseUrl(trim($attrValue));

		$error = self::validateUrl($urlConfig, $p);
		if (!empty($error))
		{
			if (isset($logger))
			{
				$p['attrValue'] = $attrValue;
				$logger->err($error, $p);
			}

			return false;
		}

		return self::rebuildUrl($p);
	}

	/**
	* Parse a URL and return its components
	*
	* Similar to PHP's own parse_url() except that all parts are always returned
	*
	* @param  string $url Original URL
	* @return array
	*/
	public static function parseUrl($url)
	{
		$regexp = '(^(?:([a-z][-+.\\w]*):)?(?://(?:([^:/?#]*)(?::([^/?#]*)?)?@)?(?:(\\[[a-f\\d:]+\\]|[^:/?#]+)(?::(\\d*))?)?(?![^/?#]))?([^?#]*)(?:\\?([^#]*))?(?:#(.*))?$)Di';

		// NOTE: this regexp always matches because of the last three captures
		preg_match($regexp, $url, $m);

		$parts  = [];
		$tokens = ['scheme', 'user', 'pass', 'host', 'port', 'path', 'query', 'fragment'];
		foreach ($tokens as $i => $name)
		{
			$parts[$name] = (isset($m[$i + 1])) ? $m[$i + 1] : '';
		}

		/**
		* @link http://tools.ietf.org/html/rfc3986#section-3.1
		*
		* 'An implementation should accept uppercase letters as equivalent to lowercase in
		* scheme names (e.g., allow "HTTP" as well as "http") for the sake of robustness but
		* should only produce lowercase scheme names for consistency.'
		*/
		$parts['scheme'] = strtolower($parts['scheme']);

		/**
		* Normalize the domain label separators and remove trailing dots
		* @link http://url.spec.whatwg.org/#domain-label-separators
		*/
		$parts['host'] = rtrim(preg_replace("/\xE3\x80\x82|\xEF(?:\xBC\x8E|\xBD\xA1)/s", '.', $parts['host']), '.');

		// Test whether host has non-ASCII characters and punycode it if possible
		if (preg_match('#[^[:ascii:]]#', $parts['host']) && function_exists('idn_to_ascii'))
		{
			$parts['host'] = idn_to_ascii($parts['host']);
		}

		return $parts;
	}

	/**
	* Rebuild a parsed URL
	*
	* @param  array  $p Parsed URL
	* @return string
	*/
	protected static function rebuildUrl(array $p)
	{
		$url = '';
		if ($p['scheme'] !== '')
		{
			$url .= $p['scheme'] . ':';
		}
		if ($p['host'] === '')
		{
			// Allow the file: scheme to not have a host and ensure it starts with slashes
			if ($p['scheme'] === 'file')
			{
				$url .= '//';
			}
		}
		else
		{
			$url .= '//';

			// Add the credentials if applicable
			if ($p['user'] !== '')
			{
				// Reencode the credentials in case there are invalid chars in them, or suspicious
				// characters such as : or @ that could confuse a browser into connecting to the
				// wrong host (or at least, to a host that is different than the one we thought)
				$url .= rawurlencode(urldecode($p['user']));

				if ($p['pass'] !== '')
				{
					$url .= ':' . rawurlencode(urldecode($p['pass']));
				}

				$url .= '@';
			}

			$url .= $p['host'];

			// Append the port number (note that as per the regexp it can only contain digits)
			if ($p['port'] !== '')
			{
				$url .= ':' . $p['port'];
			}
		}

		// Build the path, including the query and fragment parts
		$path = $p['path'];
		if ($p['query'] !== '')
		{
			$path .= '?' . $p['query'];
		}
		if ($p['fragment'] !== '')
		{
			$path .= '#' . $p['fragment'];
		}

		/**
		* "For consistency, URI producers and normalizers should use uppercase hexadecimal digits
		* for all percent- encodings."
		*
		* @link http://tools.ietf.org/html/rfc3986#section-2.1
		*/
		$path = preg_replace_callback(
			'/%.?[a-f]/',
			function ($m)
			{
				return strtoupper($m[0]);
			},
			$path
		);

		// Append the sanitized path to the URL
		$url .= self::sanitizeUrl($path);

		// Replace the first colon if there's no scheme and it could potentially be interpreted as
		// the scheme separator
		if (!$p['scheme'])
		{
			$url = preg_replace('#^([^/]*):#', '$1%3A', $url);
		}

		return $url;
	}

	/**
	* Sanitize a URL for safe use regardless of context
	*
	* This method URL-encodes some sensitive characters in case someone would want to use the URL in
	* some JavaScript thingy, or in CSS. We also encode characters that are not allowed in the path
	* of a URL as defined in RFC 3986 appendix A, including percent signs that are not immediately
	* followed by two hex digits.
	*
	* " and ' to prevent breaking out of quotes (JavaScript or otherwise)
	* ( and ) to prevent the use of functions in JavaScript (eval()) or CSS (expression())
	* < and > to prevent breaking out of <script>
	* \r and \n because they're illegal in JavaScript
	* [ and ] because the W3 validator rejects them and they "should" be escaped as per RFC 3986
	* Non-ASCII characters as per RFC 3986
	* Control codes and spaces, as per RFC 3986
	*
	* @link http://sla.ckers.org/forum/read.php?2,51478
	* @link http://timelessrepo.com/json-isnt-a-javascript-subset
	* @link http://www.ietf.org/rfc/rfc3986.txt
	* @link http://stackoverflow.com/a/1547922
	* @link http://tools.ietf.org/html/rfc3986#appendix-A
	*
	* @param  string $url Original URL
	* @return string      Sanitized URL
	*/
	public static function sanitizeUrl($url)
	{
		return preg_replace_callback(
			'/%(?![0-9A-Fa-f]{2})|[^!#-&*-;=?-Z_a-z]/S',
			function ($m)
			{
				return rawurlencode($m[0]);
			},
			$url
		);
	}

	/**
	* Validate a parsed URL
	*
	* @param  array      $urlConfig URL config
	* @param  array      $p         Parsed URL
	* @return string|null           Error message if invalid, or NULL
	*/
	protected static function validateUrl(array $urlConfig, array $p)
	{
		if ($p['scheme'] !== '' && !preg_match($urlConfig['allowedSchemes'], $p['scheme']))
		{
			return 'URL scheme is not allowed';
		}

		if ($p['host'] === '')
		{
			// Reject malformed URLs such as http:///example.org but allow schemeless paths
			if ($p['scheme'] !== 'file' && $p['scheme'] !== '')
			{
				return 'Missing host';
			}
		}
		else
		{
			/**
			* Test whether the host is valid
			* @link http://tools.ietf.org/html/rfc1035#section-2.3.1
			* @link http://tools.ietf.org/html/rfc1123#section-2
			*/
			$regexp = '/^(?!-)[-a-z0-9]{0,62}[a-z0-9](?:\\.(?!-)[-a-z0-9]{0,62}[a-z0-9])*$/i';
			if (!preg_match($regexp, $p['host']))
			{
				// If the host invalid, retest as an IPv4 and IPv6 address (IPv6 in brackets)
				if (!self::filterIpv4($p['host'])
				 && !self::filterIpv6(preg_replace('/^\\[(.*)\\]$/', '$1', $p['host'])))
				{
					return 'URL host is invalid';
				}
			}

			if ((isset($urlConfig['disallowedHosts']) && preg_match($urlConfig['disallowedHosts'], $p['host']))
			 || (isset($urlConfig['restrictedHosts']) && !preg_match($urlConfig['restrictedHosts'], $p['host'])))
			{
				return 'URL host is not allowed';
			}
		}
	}
}