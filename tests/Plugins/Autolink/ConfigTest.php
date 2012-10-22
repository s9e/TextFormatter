<?php

namespace s9e\TextFormatter\Tests\Generator\Items;

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
		$this->generator->loadPlugin('Autolink');
		$this->assertTrue($this->generator->tagExists('URL'));
	}
}