<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../../src/TextFormatter/PluginConfig.php';

class MultiRegexpConfig extends PluginConfig
{
	public function setUp()
	{
		$this->cb->addTag('X');
	}

	public function getConfig()
	{
		return array(
			'regexp' => array('#0#', '#1#'),
			'parserClassName' => __NAMESPACE__ . '\\MultiRegexpParser',
			'parserFilepath'  => __DIR__ . '/MultiRegexpParser.php'
		);
	}
}