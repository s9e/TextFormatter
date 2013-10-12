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
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;

class UrlConfig implements ConfigProvider
{
	/**
	* @var array List of allowed schemes
	*/
	protected $allowedSchemes = ['http', 'https'];

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
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$regexp = RegexpBuilder::fromList($this->allowedSchemes);
		$regexp = new Regexp('/^' . $regexp . '$/Di');
		$config = [
			'allowedSchemes' => $regexp->asConfig(),
			'requireScheme'  => $this->requireScheme
		];

		foreach (['disallowedHosts', 'resolveRedirectsHosts'] as $k)
		{
			if (!count($this->$k))
			{
				continue;
			}

			$config[$k] = $this->$k->asConfig();
		}

		if (isset($this->defaultScheme))
		{
			$config['defaultScheme'] = $this->defaultScheme;
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
		$scheme = $this->normalizeScheme($scheme);

		$this->allowedSchemes = array_values(array_diff($this->allowedSchemes, [$scheme]));
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
		$this->defaultScheme = $this->normalizeScheme($scheme);
	}
}