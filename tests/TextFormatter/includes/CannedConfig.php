<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../../src/TextFormatter/PluginConfig.php';

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