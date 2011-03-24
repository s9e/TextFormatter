<?php

namespace s9e\Toolkit\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../../src/TextFormatter/ConfigBuilder.php';
include_once __DIR__ . '/../Test.php';

class WittyPantsTest extends Test
{
	public function setUp()
	{
		$this->cb->loadPlugin('WittyPants');
	}

	protected function assertWit($text, $expected)
	{
		$this->assertSame(
			$expected,
			$this->renderer->render($this->parser->parse($text))
		);
	}

	public function testSingleQuotesEnclosingTextWithNoLineBreakAreConvertedToQuotationMarks()
	{
		$this->assertRendering(
			"'Good morning, Frank,' greeted HAL.",
			"‘Good morning, Frank,’ greeted HAL."
		);
	}

	public function testDoubleQuotesEnclosingTextWithNoLineBreakAreConvertedToQuotationMarks()
	{
		$this->assertRendering(
			'"Good morning, Frank," greeted HAL.',
			'“Good morning, Frank,” greeted HAL.'
		);
	}

	public function testSingleQuotesAfterAnEqualSignAreNotConvertedToQuotationMarks()
	{
		$this->assertRendering(
			"[url='some url']",
			"[url='some url']"
		);
	}

	public function testDoubleQuotesAfterAnEqualSignAreNotConvertedToQuotationMarks()
	{
		$this->assertRendering(
			'[url="some url"]',
			'[url="some url"]'
		);
	}

	public function testThreeConsecutiveDotsAreConvertedToAnEllipsis()
	{
		$this->assertRendering(
			'Hello world...',
			'Hello world…'
		);
	}

	public function testTwoConsecutiveHypensAreConvertedToAnEnDash()
	{
		$this->assertRendering(
			'foo--bar',
			'foo–bar'
		);
	}

	public function testThreeConsecutiveHypensAreConvertedToAnEmDash()
	{
		$this->assertRendering(
			'foo---bar',
			'foo—bar'
		);
	}

	public function testParenthesesAroundTheLettersTmInLowercaseAreReplacedWithTheTrademarkSymbol()
	{
		$this->assertRendering(
			'(tm)',
			'™'
		);
	}

	public function testParenthesesAroundTheLettersTmInUppercaseAreReplacedWithTheTrademarkSymbol()
	{
		$this->assertRendering(
			'(TM)',
			'™'
		);
	}

	public function testParenthesesAroundTheLetterCInLowercaseAreReplacedWithTheCopyrightSymbol()
	{
		$this->assertRendering(
			'(c)',
			'©'
		);
	}

	public function testParenthesesAroundTheLetterCInUppercaseAreReplacedWithTheCopyrightSymbol()
	{
		$this->assertRendering(
			'(C)',
			'©'
		);
	}

	public function testParenthesesAroundTheLetterRInLowercaseAreReplacedWithTheRegisteredSymbol()
	{
		$this->assertRendering(
			'(r)',
			'®'
		);
	}

	public function testParenthesesAroundTheLetterRInUppercaseAreReplacedWithTheRegisteredSymbol()
	{
		$this->assertRendering(
			'(R)',
			'®'
		);
	}
}