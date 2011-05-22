<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\AutolinkConfig
*/
class AutolinkConfigTest extends Test
{
	/**
	* @test
	*/
	public function Automatically_creates_an_URL_tag()
	{
		$this->cb->loadPlugin('Autolink');
		$this->assertTrue($this->cb->tagExists('URL'));
	}

	/**
	* @test
	*/
	public function Generates_a_regexp_that_matches_all_possible_URLs()
	{
		$this->assertArrayMatches(
			array(
				'regexp' => '#https?://\S+#iS'
			),
			$this->cb->loadPlugin('Autolink')->getConfig()
		);
	}
}