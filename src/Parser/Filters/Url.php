<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser\Filters;

use s9e\TextFormatter\Parser\FilterBase;
use s9e\TextFormatter\Parser\Logger;

class Url extends FilterBase
{
	/**
	* Filter an URL
	*
	* @param  mixed  $attrValue Original URL
	* @param  array  $urlConfig URL config
	* @param  Logger $logger    Parser's logger
	* @return mixed             Cleaned up URL if valid, FALSE otherwise
	*/
	public static function filter($attrValue, array $urlConfig, Logger $logger)
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

		if (substr($attrValue, 0, 2) === '//'
		 && isset($urlConfig['defaultScheme']))
		{
			 $attrValue = $urlConfig['defaultScheme'] . ':' . $attrValue;
			 $removeScheme = true;
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
				'"' => '%22',
				"'" => '%27',
				'(' => '%28',
				')' => '%29'
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

		if (!preg_match($urlConfig['allowedSchemes'], $p['scheme']))
		{
			$logger->error(array(
				'msg'    => "URL scheme '%s' is not allowed",
				'params' => array($p['scheme'])
			));
			return false;
		}

		if (isset($urlConfig['disallowedHosts'])
		 && preg_match($urlConfig['disallowedHosts'], $p['host']))
		{
			$logger->error(array(
				'msg'    => "URL host '%s' is not allowed",
				'params' => array($p['host'])
			));
			return false;
		}

		if (isset($urlConfig['resolveRedirectsHosts'])
		 && preg_match($urlConfig['resolveRedirectsHosts'], $p['host'])
		 && preg_match('#^https?#i', $p['scheme']))
		{
			if (isset($followedUrls[$attrValue]))
			{
				$logger->error(array(
					'msg'    => 'Infinite recursion detected while following %s',
					'params' => array($attrValue)
				));
				return false;
			}

			$redirect = self::getRedirectLocation($attrValue);

			if ($redirect === false)
			{
				$logger->error(array(
					'msg'    => 'Could not resolve %s',
					'params' => array($attrValue)
				));
				return false;
			}

			if (isset($redirect))
			{
				$logger->debug(array(
					'msg'    => 'Followed redirect from %1$s to %2$s',
					'params' => array($attrValue, $redirect)
				));

				$followedUrls[$attrValue] = 1;
				$attrValue = $redirect;

				goto checkUrl;
			}

			$logger->debug(array(
				'msg'    => 'No Location: received from %s',
				'params' => array($attrValue)
			));
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
	* @param  string $attrValue Request URL
	* @return mixed       Location URL if applicable, FALSE in case of error, NULL if no Location
	*/
	protected function getRedirectLocation($attrValue)
	{
		$fp = @fopen(
			$attrValue,
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
	* @param  string $attrValue Original URL
	* @return Mixed       Encoded URL
	*/
	static protected function encodeUrlToAscii($attrValue)
	{
		if (function_exists('idn_to_ascii')
		 && preg_match('#^([^:]+://(?:[^/]+@)?)([^/]+)#i', $attrValue, $m))
		{
			$attrValue = $m[1] . idn_to_ascii($m[2]) . substr($attrValue, strlen($m[0]));
		}

		// URL-encode non-ASCII stuff
		return preg_replace_callback(
			'#[^[:ascii:]]+#u',
			function ($m)
			{
				return urlencode($m[0]);
			},
			$attrValue
		);
	}
}