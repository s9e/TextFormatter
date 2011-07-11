<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../src/PluginConfig.php';

class CannedConfig extends PluginConfig
{
	public $tags = array();

	public function getConfig()
	{
		return array(
			'parserClassName' => __NAMESPACE__ . '\\CannedParser',
			'parserFilepath'  => __DIR__ . '/CannedParser.php',
			'tags' => $this->tags
		);
	}
}