<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;

use ArrayObject;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;

class MediaSiteCollection extends ArrayObject implements ConfigProvider
{
	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$map = [];
		foreach ($this as $siteId => $siteConfig)
		{
			foreach ((array) $siteConfig['host'] as $host)
			{
				$map[$host] = $siteId;
			}
		}

		// Create a JS variant as a dictionary to preserve the keys (which hold the hosts)
		$config = new Variant($map);
		$config->set('JS', new Dictionary($map));

		return $config;
	}
}