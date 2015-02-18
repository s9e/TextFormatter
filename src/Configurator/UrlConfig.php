<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\HostnameList;
use s9e\TextFormatter\Configurator\Collections\SchemeList;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;

class UrlConfig implements ConfigProvider
{
	protected $allowedSchemes;

	protected $disallowedHosts;

	protected $disallowedSubstrings = [];

	protected $restrictedHosts;

	public function __construct()
	{
		$this->disallowedHosts = new HostnameList;
		$this->restrictedHosts = new HostnameList;

		$this->allowedSchemes   = new SchemeList;
		$this->allowedSchemes[] = 'http';
		$this->allowedSchemes[] = 'https';
	}

	public function asConfig()
	{
		$vars = \get_object_vars($this);
		if (empty($vars['disallowedSubstrings']))
			unset($vars['disallowedSubstrings']);
		else
		{
			$regexp = '#' . RegexpBuilder::fromList($vars['disallowedSubstrings'], ['specialChars' => ['*' => '.*?']]) . '#i';
			if (\preg_match('([^[:ascii:]])', $regexp))
				$regexp .= 'u';
			$vars['disallowedSubstrings'] = $regexp;
		}

		return ConfigHelper::toArray($vars);
	}

	public function allowScheme($scheme)
	{
		if (\strtolower($scheme) === 'javascript')
			throw new RuntimeException('The JavaScript URL scheme cannot be allowed');

		$this->allowedSchemes[] = $scheme;
	}

	public function disallowHost($host, $matchSubdomains = \true)
	{
		$this->disallowedHosts[] = $host;

		if ($matchSubdomains && \substr($host, 0, 1) !== '*')
			$this->disallowedHosts[] = '*.' . $host;
	}

	public function disallowScheme($scheme)
	{
		$this->allowedSchemes->remove($scheme);
	}

	public function disallowSubstring($str)
	{
		$this->disallowedSubstrings[] = $str;
	}

	public function getAllowedSchemes()
	{
		return \iterator_to_array($this->allowedSchemes);
	}

	public function restrictHost($host, $matchSubdomains = \true)
	{
		$this->restrictedHosts[] = $host;

		if ($matchSubdomains && \substr($host, 0, 1) !== '*')
			$this->restrictedHosts[] = '*.' . $host;
	}
}