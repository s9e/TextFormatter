<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;

use InvalidArgumentException;
use RuntimeException;

abstract class SiteDefinitionProvider
{
	/**
	* Get the default config for given site
	*
	* @param  string $siteId Site'd ID, e.g. "youtube"
	* @return array          Site's config
	*/
	public function get($siteId)
	{
		$siteId = $this->normalizeId($siteId);
		if (!$this->hasSiteConfig($siteId))
		{
			throw new RuntimeException("Unknown media site '" . $siteId . "'");
		}

		return $this->getSiteConfig($siteId);
	}

	/**
	* Get the IDs of all supported sites
	*
	* @return string[] Site IDs
	*/
	abstract public function getIds();

	/**
	* Test whether given site exists
	*
	* @param  string $siteId Site'd ID, e.g. "youtube"
	* @return bool
	*/
	public function has($siteId)
	{
		return $this->hasSiteConfig($this->normalizeId($siteId));
	}

	/**
	* Get the default config for given site
	*
	* @param  string $siteId Site'd ID, e.g. "youtube"
	* @return array          Site's config
	*/
	abstract protected function getSiteConfig($siteId);

	/**
	* Test whether given site exists
	*
	* @param  string $siteId Site'd ID, e.g. "youtube"
	* @return bool
	*/
	abstract protected function hasSiteConfig($siteId);

	/**
	* Validate and normalize a site ID
	*
	* @param  string $siteId
	* @return string
	*/
	protected function normalizeId($siteId)
	{
		$siteId = strtolower($siteId);
		if (!preg_match('(^[a-z0-9]+$)', $siteId))
		{
			throw new InvalidArgumentException('Invalid site ID');
		}

		return $siteId;
	}
}