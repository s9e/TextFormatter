<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;

use DOMDocument;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;

class MediaPack extends Bundle
{
	/**
	* {@inheritdoc}
	*/
	public function configure(Configurator $configurator)
	{
		if (!isset($configurator->MediaEmbed))
		{
			// Only create BBCodes if the BBCodes plugin is already loaded
			$pluginOptions = ['createMediaBBCode' => isset($configurator->BBCodes)];

			$configurator->plugins->load('MediaEmbed', $pluginOptions);
		}

		foreach ($configurator->MediaEmbed->defaultSites as $siteId => $siteConfig)
		{
			$configurator->MediaEmbed->add($siteId);
		}
	}
}