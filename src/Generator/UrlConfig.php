<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Generator;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Generator\Helpers\RegexpBuilder;

class UrlConfig implements ConfigProvider
{
	/**
	* @var array List of allowed schemes
	*/
	protected $allowedSchemes = array('http', 'https');

	/**
	* @var array List of disallowed hosts
	*/
	protected $disallowedHosts = array();

	/**
	* @var array List of hosts whose URL we check for redirects
	*/
	protected $resolveRedirectsHosts = array();

	/**
	* @var string Default scheme to be used when validating schemeless URLs
	*/
	protected $defaultScheme;

	/**
	* {@inheritdoc}
	*/
	public function toConfig()
	{
		$config = array(
			'allowedSchemes' => '/^' . RegexpBuilder::fromList($this->allowedSchemes) . '$/Di'
		);

		if (isset($this->defaultScheme))
		{
			$config['defaultScheme'] = $this->defaultScheme;
		}

		foreach (array('disallowedHosts', 'resolveRedirectsHosts') as $k)
		{
			if (empty($this->$k))
			{
				continue;
			}

			$regexp = RegexpBuilder::fromList(
				$this->$k,
				// Asterisks * are turned into a catch-all expression, while ^ and $ are preserved
				array(
					'specialChars' => array(
						'*' => '.*',
						'^' => '^',
						'$' => '$'
					)
				)
			);

			$config[$k] = '/' . $regexp . '/DiS';
		}

		return $config;
	}

	/**
	* Allow a URL scheme
	*
	* @param string $scheme URL scheme, e.g. "file" or "ed2k"
	*/
	public function allowScheme($scheme)
	{
		$scheme = $this->normalizeScheme($scheme);

		$this->allowedSchemes[] = $scheme;
	}

	/**
	* Set a default scheme to be used for validation of scheme-less URLs
	*
	* @param string $scheme URL scheme, e.g. "http" or "https"
	*/
	public function setDefaultScheme($scheme)
	{
		$this->defaultScheme = $this->normalizeScheme($scheme);
	}

	/**
	* Validate and normalize a scheme name to lowercase, or throw an exception if invalid
	*
	* @link http://tools.ietf.org/html/rfc3986#section-3.1
	*
	* @param  string $scheme URL scheme, e.g. "file" or "ed2k"
	* @return string
	*/
	protected function normalizeScheme($scheme)
	{
		if (!preg_match('#^[a-z][a-z0-9+\\-.]*$#Di', $scheme))
		{
			throw new InvalidArgumentException("Invalid scheme name '" . $scheme . "'");
		}

		return strtolower($scheme);
	}

	/**
	* Return the list of allowed URL schemes
	*
	* @return array
	*/
	public function getAllowedSchemes()
	{
		return $this->allowedSchemes;
	}

	/**
	* Disallow a hostname (or hostname mask) from being used in URLs
	*
	* @param string $host Hostname or hostmask
	*/
	public function disallowHost($host)
	{
		$this->disallowedHosts[] = $this->normalizeHostmask($host);
	}

	/**
	* Force URLs from given hostmask to be followed and resolved to their true location
	*
	* @param string $host Hostname or hostmask
	*/
	public function resolveRedirectsFrom($host)
	{
		$this->resolveRedirectsHosts[] = $this->normalizeHostmask($host);
	}

	/**
	* @param  string $host Hostname or hostmask
	* @return string
	*/
	protected function normalizeHostmask($host)
	{
		if (preg_match('#[\\x80-\xff]#', $host))
		{
			// @codeCoverageIgnoreStart
			if (!function_exists('idn_to_ascii'))
			{
				throw new RuntimeException('Cannot handle IDNs without the Intl PHP extension');
			}
			// @codeCoverageIgnoreEnd

			$host = idn_to_ascii($host);
		}

		if (substr($host, 0, 1) === '*')
		{
			// *.example.com => #\.example\.com$#
			$host = ltrim($host, '*');
		}
		else
		{
			// example.com => #^example\.com$#
			$host = '^' . $host;
		}

		if (substr($host, -1) === '*')
		{
			// example.* => #^example\.#
			$host = rtrim($host, '*');
		}
		else
		{
			// example.com => #^example\.com$#
			$host .= '$';
		}

		return $host;
	}
}