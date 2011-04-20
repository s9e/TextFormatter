<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\AutolinkConfig
*/
class AutolinkConfigTest extends Test
{
	public function testAutomaticallyCreatesAnUrlTag()
	{
		$this->cb->loadPlugin('Autolink');
		$this->assertTrue($this->cb->tagExists('URL'));
	}

	public function testGeneratesARegexpThatMatchesAllPossibleUrls()
	{
		$this->assertArrayMatches(
			array(
				'regexp' => '#https?://\S+#iS'
			),
			$this->cb->loadPlugin('Autolink')->getConfig()
		);
	}
}