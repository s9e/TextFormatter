<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Bundles;

use DOMDocument;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundle;

class MediaPack extends Bundle
{
	/*
	* {@inheritdoc}
	*/
	public function configure(Configurator $configurator)
	{
		if (!isset($configurator->MediaEmbed))
		{
			// Only create BBCodes if the BBCodes plugin is already loaded
			$pluginOptions = array('createBBCodes' => isset($configurator->BBCodes));

			$configurator->plugins->load('MediaEmbed', $pluginOptions);
		}

		foreach (\glob($configurator->MediaEmbed->sitesDir . '/*.xml') as $siteFile)
		{
			$siteId = \basename($siteFile, '.xml');
			$configurator->MediaEmbed->add($siteId);
		}
	}
}