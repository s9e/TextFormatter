<?php

namespace s9e\Toolkit\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../src/TextFormatter/ConfigBuilder.php';
include_once __DIR__ . '/../Test.php';

class WittyPantsTest extends Test
{
	protected function assertWit($text, $expected)
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertSame(
			$expected,
			$this->renderer->render($this->parser->parse($text))
		);
	}

	public function testThreeConsecutiveDotsAreConvertedIntoAnEllipsis()
	{
		$this->assertWit(
			'Hello world...',
			"Hello world\xE2\x80\xA6"
		);
	}
}