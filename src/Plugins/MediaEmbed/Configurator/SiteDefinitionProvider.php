<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;

use InvalidArgumentException;

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
		return $this->getSiteConfig($this->normalizeId($siteId));
	}

	/**
	* Get the IDs of all supported sites
	*
	* @return string[] Site IDs
	*/
	abstract public function getIds();

	/**
	* Get the default config for given site
	*
	* @param  string $siteId Site'd ID, e.g. "youtube"
	* @return array          Site's config
	*/
	abstract protected function getSiteConfig($siteId);

	/**
	* Validate and normalize a site ID
	*
	* @param  string $siteId
	* @return string
	*/
	public function normalizeId($siteId)
	{
		$siteId = strtolower($siteId);
		if (!preg_match('(^[a-z0-9]+$)', $siteId))
		{
			throw new InvalidArgumentException('Invalid site ID');
		}

		return $siteId;
	}
}