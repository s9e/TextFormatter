<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
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
			$pluginOptions = array('createMediaBBCode' => isset($configurator->BBCodes));

			$configurator->plugins->load('MediaEmbed', $pluginOptions);
		}

		foreach ($configurator->MediaEmbed->defaultSites->getIds() as $siteId)
		{
			$configurator->MediaEmbed->add($siteId);
		}
	}
}