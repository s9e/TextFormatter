<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
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
		$followedUrls = [];
		checkUrl:

		/**
		* Trim the URL to conform with HTML5
		* @link http://dev.w3.org/html5/spec/links.html#attr-hyperlink-href
		*/
		$attrValue = trim($attrValue);

		/**
		* @var bool Whether to remove the scheme part of the URL
		*/
		$removeScheme = false;

		/**
		* @var bool Whether to validate the scheme part of the URL
		*/
		$validateScheme = true;

		if (substr($attrValue, 0, 2) === '//')
		{
			if (isset($urlConfig['defaultScheme']))
			{
				$attrValue    = $urlConfig['defaultScheme'] . ':' . $attrValue;
				$removeScheme = true;
			}
			elseif (empty($urlConfig['requireScheme']))
			{
				$attrValue      = 'http:' . $attrValue;
				$removeScheme   = true;
				$validateScheme = false;
			}
		}

		// Test whether the URL contains only ASCII characters, and encode them if not
		if (!preg_match('#^[[:ascii]]+$#D', $attrValue))
		{
			$attrValue = self::encodeUrlToAscii($attrValue);
		}

		// We URL-encode some sensitive characters in case someone would want to use the URL in
		// some JavaScript thingy, or in CSS
		$attrValue = strtr(
			$attrValue,
			[
				// Prevents breaking out of quotes
				'"' => '%22',
				"'" => '%27',

				// Prevents the use of functions in JS (eval()) and CSS (expression())
				'(' => '%28',
				')' => '%29',

				/**
				* Prevents breaking out of <script>
				* @link http://sla.ckers.org/forum/read.php?2,51478
				*/
				'<' => '%3C',
				'>' => '%3E',

				/**
				* Those are illegal in JavaScript
				* @link http://timelessrepo.com/json-isnt-a-javascript-subset
				*
				* NOTE: the Unicode terminators would get escaped by encodeUrlToAscii() anyway
				*/
				"\x0A" => '%0A',
				"\x0D" => '%0D',
				"\xE2\x80\xA8" => '%E2%80%A8',
				"\xE2\x80\xA9" => '%E2%80%A9'
			]
		);

		// Validate the URL
		$attrValue = filter_var($attrValue, FILTER_VALIDATE_URL);

		if (!$attrValue)
		{
			return false;
		}

		// Now parse it to check its scheme and host
		$p = parse_url($attrValue);

		// Save the host part if available, remove trailing dots from the hostname
		$host = (isset($p['host'])) ? rtrim($p['host'], '.') : '';

		if ($validateScheme && !preg_match($urlConfig['allowedSchemes'], $p['scheme']))
		{
			if (isset($logger))
			{
				$logger->err(
					'URL scheme is not allowed',
					['attrValue' => $attrValue, 'scheme' => $p['scheme']]
				);
			}

			return false;
		}

		if ((isset($urlConfig['disallowedHosts']) && preg_match($urlConfig['disallowedHosts'], $host))
		 || (isset($urlConfig['restrictedHosts']) && !preg_match($urlConfig['restrictedHosts'], $host)))
		{
			if (isset($logger))
			{
				$logger->err(
					'URL host is not allowed',
					['attrValue' => $attrValue, 'host' => $host]
				);
			}

			return false;
		}

		if (isset($urlConfig['resolveRedirectsHosts'])
		 && preg_match($urlConfig['resolveRedirectsHosts'], $host)
		 && preg_match('#^https?#i', $p['scheme']))
		{
			if (isset($followedUrls[$attrValue]))
			{
				if (isset($logger))
				{
					$logger->err(
						'Infinite recursion detected while following redirects',
						['attrValue' => $attrValue]
					);
				}

				return false;
			}

			$redirect = self::getRedirectLocation($attrValue);

			if ($redirect === false)
			{
				if (isset($logger))
				{
					$logger->err(
						'Could not resolve redirect',
						['attrValue' => $attrValue]
					);
				}

				return false;
			}

			if (isset($redirect))
			{
				if (isset($logger))
				{
					$logger->debug(
						'Resolved redirect',
						['from' => $attrValue, 'to' => $redirect]
					);
				}

				$followedUrls[$attrValue] = 1;
				$attrValue = $redirect;

				goto checkUrl;
			}
		}

		// Normalize scheme, or remove if applicable
		$pos = strpos($attrValue, ':');

		if ($removeScheme)
		{
			$attrValue = substr($attrValue, $pos + 1);
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
			$attrValue = strtolower(substr($attrValue, 0, $pos)) . substr($attrValue, $pos);
		}

		return $attrValue;
	}

	/**
	* Get the "Location:" value returned by an HTTP(S) query
	*
	* @param  string $url Request URL
	* @return mixed       Location URL if applicable, FALSE in case of error, NULL if no Location
	*/
	protected static function getRedirectLocation($url)
	{
		$fp = @fopen(
			$url,
			'rb',
			false,
			stream_context_create([
				'http' => [
					// Bit.ly doesn't like HEAD =\
					//'method' => 'HEAD',
					'header' => "Connection: close\r\n",
					'follow_location' => false
				]
			])
		);

		if (!$fp)
		{
			return false;
		}

		$meta = stream_get_meta_data($fp);
		fclose($fp);

		foreach ($meta['wrapper_data'] as $k => $line)
		{
			if (is_numeric($k)
			 && preg_match('#^Location:(.*)#i', $line, $m))
			{
				return trim($m[1]);
			}
		}

		return null;
	}

	/**
	* Encode an UTF-8 URL to ASCII
	*
	* Requires idn_to_ascii() in order to deal with IDNs. If idn_to_ascii() is not available, the
	* host part will be URL-encoded with the rest of the URL.
	*
	* @param  string $url Original URL
	* @return Mixed       Encoded URL
	*/
	protected static function encodeUrlToAscii($url)
	{
		if (function_exists('idn_to_ascii')
		 && preg_match('#^([^:]+://(?>[^/@]+@)?)([^/]+)#i', $url, $m))
		{
			$url = $m[1] . idn_to_ascii($m[2]) . substr($url, strlen($m[0]));
		}

		// URL-encode non-ASCII stuff
		return preg_replace_callback(
			'#[^[:ascii:]]+#',
			function ($m)
			{
				return urlencode($m[0]);
			},
			$url
		);
	}
}