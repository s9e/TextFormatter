<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../Test.php';

class WittyPantsTest extends Test
{
	public function setUp()
	{
		$this->cb->loadPlugin('WittyPants');
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

	public function testASingleQuoteBeforeALetterAtTheStartOfALineIsReplacedWithAnApostrophe()
	{
		$this->assertRendering(
			"'Twas the night.\n'Twas the night before Christmas.",
			"’Twas the night.\n’Twas the night before Christmas."
		);
	}


	public function testASingleQuoteBetweenTwoLettersIsReplacedWithAnApostrophe()
	{
		$this->assertRendering(
			"Occam's razor",
			"Occam’s razor"
		);
	}

	public function testASingleQuoteBeforeATwoDigitsNumberAtTheStartOfALineIsReplacedWithAnApostrophe()
	{
		$this->assertRendering(
			"'88 was the year.\n'88 was the year indeed.",
			"’88 was the year.\n’88 was the year indeed."
		);
	}

	/**
	* @depends testSingleQuotesEnclosingTextWithNoLineBreakAreConvertedToQuotationMarks
	*/
	public function testASingleQuoteThatIsPartOfAPairOfQuotationMarksIsNotReplacedWithAnApostrophe()
	{
		$this->assertRendering(
			"'88 bottles of beer on the wall'",
			"‘88 bottles of beer on the wall’"
		);
	}

	public function testASingleQuoteAfterADigitsAndBeforeTheLetterSIsReplacedWithAnApostrophe()
	{
		$this->assertRendering(
			"1950's",
			"1950’s"
		);
	}
}