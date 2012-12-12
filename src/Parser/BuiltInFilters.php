<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

class BuiltInFilters
{
	/**
	* Filter a color value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterColor($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '/^(?:#[0-9a-f]{3,6}|[a-z]+)$/Di')
		));
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
	* Filter an id value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterId($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '#^[A-Za-z0-9\\-_]+$#D')
		));
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
	* Filter a numbervalue
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterNumber($attrValue)
	{
		return (preg_match('#^[0-9]+$#D', $attrValue))
			  ? $attrValue
			  : false;
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
	public static function filterRange($attrValue, $min, $max, Logger $logger)
	{
		$attrValue = filter_var($attrValue, FILTER_VALIDATE_INT);

		if ($attrValue === false)
		{
			return false;
		}

		if ($attrValue < $min)
		{
			$logger->warn(
				'Value outside of range, adjusted up to min value',
				array(
					'attrValue' => $attrValue,
					'min'       => $min,
					'max'       => $max
				)
			);

			return $min;
		}

		if ($attrValue > $max)
		{
			$logger->warn(
				'Value outside of range, adjusted down to max value',
				array(
					'attrValue' => $attrValue,
					'min'       => $min,
					'max'       => $max
				)
			);

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
		return (preg_match($regexp, $attrValue))
		     ? $attrValue
		     : false;
	}

	/**
	* Filter a simpletext value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterSimpletext($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '#^[A-Za-z0-9\\-+.,_ ]+$#D')
		));
	}

	/**
	* Filter a uint value
	*
	* @param  string $attrValue Original value
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filterUint($attrValue)
	{
		return filter_var($attrValue, FILTER_VALIDATE_INT, array(
			'options' => array('min_range' => 0)
		));
	}

	/**
	* Filter an URL
	*
	* @param  mixed  $attrValue Original URL
	* @param  array  $urlConfig URL config
	* @param  Logger $logger    Parser's logger
	* @return mixed             Cleaned up URL if valid, FALSE otherwise
	*/
	public static function filterUrl($attrValue, array $urlConfig, Logger $logger)
	{
		$followedUrls = array();
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

		// Test whether the URL contains only ASCII characters
		if (!preg_match('#^[[:ascii]]+$#D', $attrValue))
		{
			$attrValue = self::encodeUrlToAscii($attrValue);
		}

		// We URL-encode quotes and parentheses just in case someone would want to use the URL in
		// some Javascript thingy, or in CSS
		$attrValue = strtr(
			$attrValue,
			array(
				'"' => '%22', "'" => '%27',
				'(' => '%28', ')' => '%29'
			)
		);

		// Validate the URL
		$attrValue = filter_var($attrValue, FILTER_VALIDATE_URL);

		if (!$attrValue)
		{
			return false;
		}

		// Now parse it to check its scheme and host
		$p = parse_url($attrValue);

		if ($validateScheme && !preg_match($urlConfig['allowedSchemes'], $p['scheme']))
		{
			$logger->err(
				'URL scheme is not allowed',
				array('attrValue' => $attrValue, 'scheme' => $p['scheme'])
			);

			return false;
		}

		if (isset($urlConfig['disallowedHosts'])
		 && preg_match($urlConfig['disallowedHosts'], $p['host']))
		{
			$logger->err(
				'URL host is not allowed',
				array('attrValue' => $attrValue, 'host' => $p['host'])
			);

			return false;
		}

		if (isset($urlConfig['resolveRedirectsHosts'])
		 && preg_match($urlConfig['resolveRedirectsHosts'], $p['host'])
		 && preg_match('#^https?#i', $p['scheme']))
		{
			if (isset($followedUrls[$attrValue]))
			{
				$logger->err(
					'Infinite recursion detected while following redirects',
					array('attrValue' => $attrValue)
				);

				return false;
			}

			$redirect = self::getRedirectLocation($attrValue);

			if ($redirect === false)
			{
				$logger->err(
					'Could not resolve redirect',
					array('attrValue' => $attrValue)
				);

				return false;
			}

			if (isset($redirect))
			{
				$logger->debug(
					'Resolved redirect',
					array('from' => $attrValue, 'to' => $redirect)
				);

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
			stream_context_create(array(
				'http' => array(
					// Bit.ly doesn't like HEAD =\
					//'method' => 'HEAD',
					'header' => "Connection: close\r\n",
					'follow_location' => false
				)
			))
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
		 && preg_match('#^([^:]+://(?:[^/]+@)?)([^/]+)#i', $url, $m))
		{
			$url = $m[1] . idn_to_ascii($m[2]) . substr($url, strlen($m[0]));
		}

		// URL-encode non-ASCII stuff
		return preg_replace_callback(
			'#[^[:ascii:]]+#u',
			function ($m)
			{
				return urlencode($m[0]);
			},
			$url
		);
	}
}