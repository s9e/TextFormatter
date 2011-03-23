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

	public function testSingleQuotesEnclosingTextWithNoLineBreakAreConvertedToQuotationMarks()
	{
		$this->assertWit(
			"'Good morning, Frank,' greeted HAL.",
			"‘Good morning, Frank,’ greeted HAL."
		);
	}

	public function testDoubleQuotesEnclosingTextWithNoLineBreakAreConvertedToQuotationMarks()
	{
		$this->assertWit(
			'"Good morning, Frank," greeted HAL.',
			'“Good morning, Frank,” greeted HAL.'
		);
	}

	public function testSingleQuotesAfterAnEqualSignAreNotConvertedToQuotationMarks()
	{
		$this->assertWit(
			"[url='some url']",
			"[url='some url']"
		);
	}

	public function testDoubleQuotesAfterAnEqualSignAreNotConvertedToQuotationMarks()
	{
		$this->assertWit(
			'[url="some url"]',
			'[url="some url"]'
		);
	}

	public function testThreeConsecutiveDotsAreConvertedToAnEllipsis()
	{
		$this->assertWit(
			'Hello world...',
			'Hello world…'
		);
	}

	public function testTwoConsecutiveHypensAreConvertedToAnEnDash()
	{
		$this->assertWit(
			'foo--bar',
			'foo–bar'
		);
	}

	public function testThreeConsecutiveHypensAreConvertedToAnEmDash()
	{
		$this->assertWit(
			'foo---bar',
			'foo—bar'
		);
	}
}