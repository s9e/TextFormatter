<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../../src/TextFormatter/PluginConfig.php';

class MyJsPluginConfig extends PluginConfig
{
	public function getJSConfig()
	{
		return array('foo' => 'bar');
	}

	public function getJSConfigMeta()
	{
		return array('baz' => 'quux');
	}

	public function getJSParser()
	{
		return 'alert("Hello mom")';
	}

	public function getConfig()
	{
		throw new \Exception('getConfig() was called');
	}
}