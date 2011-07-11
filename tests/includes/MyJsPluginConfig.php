<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../src/PluginConfig.php';

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