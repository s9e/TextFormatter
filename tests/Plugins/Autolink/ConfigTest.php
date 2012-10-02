<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Items;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\Autolink\Config;

/**
* @covers s9e\TextFormatter\Plugins\Autolink\Config
*/
class ConfigTest extends Test
{
	/**
	* @testdox Automatically creates an URL tag
	*/
	public function Automatically_creates_an_URL_tag()
	{
		$this->cb->loadPlugin('Autolink');
		$this->assertTrue($this->cb->tagExists('URL'));
	}
}