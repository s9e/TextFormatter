<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../src/PluginConfig.php';

class AnotherJsPluginConfig extends PluginConfig
{
	public $js = 'alert("Hi mom")';

	public function getConfig()
	{
		$config = array();

		foreach (array('regexp', 'regexpLimitAction') as $k)
		{
			if (isset($this->$k))
			{
				$config[$k] = $this->$k;
			}
		}

		return $config;
	}

	public function getJSParser()
	{
		return $this->js;
	}
}