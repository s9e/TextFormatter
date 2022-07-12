<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

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
	* @var HostnameList List of disallowed hosts
	*/
	protected $disallowedHosts;

	/**
	* @var HostnameList List of allowed hosts
	*/
	protected $restrictedHosts;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->disallowedHosts = new HostnameList;
		$this->restrictedHosts = new HostnameList;

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
	* @return void
	*/
	public function allowScheme($scheme)
	{
		if (strtolower($scheme) === 'javascript')
		{
			throw new RuntimeException('The JavaScript URL scheme cannot be allowed');
		}

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
	* @param  string $scheme URL scheme, e.g. "file" or "ed2k"
	* @return void
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
}