<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;

use InvalidArgumentException;

abstract class SiteDefinitionProvider
{
	public function get($siteId)
	{
		return $this->getSiteConfig($this->normalizeId($siteId));
	}

	abstract public function getIds();

	abstract protected function getSiteConfig($siteId);

	public function normalizeId($siteId)
	{
		$siteId = \strtolower($siteId);
		if (!\preg_match('(^[a-z0-9]+$)', $siteId))
			throw new InvalidArgumentException('Invalid site ID');

		return $siteId;
	}
}