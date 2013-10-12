<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\HostnameList;
use s9e\TextFormatter\Configurator\Collections\SchemeList;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;

class UrlConfig implements ConfigProvider
{
	/**
	* @var SchemeList List of allowed schemes
	*/
	protected $allowedSchemes;

	/**
	* @var string Default scheme to be used when validating scheme-less URLs
	*/
	protected $defaultScheme;

	/**
	* @var HostnameList List of disallowed hosts
	*/
	protected $disallowedHosts;

	/**
	* @var HostnameList List of hosts whose URL we check for redirects
	*/
	protected $resolveRedirectsHosts;

	/**
	* @var HostnameList List of allowed hosts
	*/
	protected $restrictedHosts;

	/**
	* @var bool Whether URLs should require a scheme
	* @link http://tools.ietf.org/html/rfc3986#section-4.2
	*/
	protected $requireScheme = false;

	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->disallowedHosts       = new HostnameList;
		$this->resolveRedirectsHosts = new HostnameList;
		$this->restrictedHosts       = new HostnameList;

		$this->allowedSchemes   = new SchemeList;
		$this->allowedSchemes[] = 'http';
		$this->allowedSchemes[] = 'https';
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		return ConfigHelper::toArray(get_object_vars($this));
	}

	/**
	* Allow a URL scheme
	*
	* @param string $scheme URL scheme, e.g. "file" or "ed2k"
	*/
	public function allowScheme($scheme)
	{
		$this->allowedSchemes[] = $scheme;
	}

	/**
	* Disallow a hostname (or hostname mask) from being used in URLs
	*
	* @param  string $host            Hostname or hostmask
	* @param  bool   $matchSubdomains Whether to match subdomains of given host
	* @return void
	*/
	public function disallowHost($host, $matchSubdomains = true)
	{
		$this->disallowedHosts[] = $host;

		if ($matchSubdomains && substr($host, 0, 1) !== '*')
		{
			$this->disallowedHosts[] = '*.' . $host;
		}
	}

	/**
	* Remove a scheme from the list of allowed URL schemes
	*
	* @param string $scheme URL scheme, e.g. "file" or "ed2k"
	*/
	public function disallowScheme($scheme)
	{
		$this->allowedSchemes->remove($scheme);
	}

	/**
	* Return the list of allowed URL schemes
	*
	* @return array
	*/
	public function getAllowedSchemes()
	{
		return iterator_to_array($this->allowedSchemes);
	}

	/**
	* Force URLs from given hostmask to be followed and resolved to their true location
	*
	* @param string $host            Hostname or hostmask
	* @param bool   $matchSubdomains Whether to match subdomains of given host
	*/
	public function resolveRedirectsFrom($host, $matchSubdomains = true)
	{
		$this->resolveRedirectsHosts[] = $host;

		if ($matchSubdomains && substr($host, 0, 1) !== '*')
		{
			$this->resolveRedirectsHosts[] = '*.' . $host;
		}
	}

	/**
	* Allow a hostname (or hostname mask) to being used in URLs while disallowing everything else
	*
	* Can be called multiple times to restricts URLs to a set of given hostnames
	*
	* @param  string $host            Hostname or hostmask
	* @param  bool   $matchSubdomains Whether to match subdomains of given host
	* @return void
	*/
	public function restrictHost($host, $matchSubdomains = true)
	{
		$this->restrictedHosts[] = $host;

		if ($matchSubdomains && substr($host, 0, 1) !== '*')
		{
			$this->restrictedHosts[] = '*.' . $host;
		}
	}

	/**
	* Force URLs to have a scheme part
	*
	* @param bool $bool Whether to disallow scheme-less URLs
	*/
	public function requireScheme($bool = true)
	{
		if (!is_bool($bool))
		{
			throw new InvalidArgumentException('requireScheme() expects a boolean');
		}

		$this->requireScheme = $bool;
	}

	/**
	* Set a default scheme to be used for validation of scheme-less URLs
	*
	* @param string $scheme URL scheme, e.g. "http" or "https"
	*/
	public function setDefaultScheme($scheme)
	{
		$this->defaultScheme = $this->allowedSchemes->normalizeValue($scheme);
	}
}