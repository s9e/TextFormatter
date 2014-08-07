<?php

/**
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
	/**
	* {@inheritdoc}
	*/
	public function configure(Configurator $configurator)
	{
		if (!isset($configurator->MediaEmbed))
		{
			// Only create BBCodes if the BBCodes plugin is already loaded
			$pluginOptions = ['createBBCodes' => isset($configurator->BBCodes)];

			$configurator->plugins->load('MediaEmbed', $pluginOptions);
		}

		$dom = new DOMDocument;
		$dom->load(__DIR__ . '/../../Plugins/MediaEmbed/Configurator/sites.xml');

		foreach ($dom->getElementsByTagName('site') as $site)
		{
			$configurator->MediaEmbed->add($site->getAttribute('id'));
		}
	}
}