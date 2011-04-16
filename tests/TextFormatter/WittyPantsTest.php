<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../Test.php';

class WittyPantsTest extends Test
{
	public function testSingleQuotesEnclosingTextWithNoLineBreakAreConvertedToQuotationMarks()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"'Good morning, Frank,' greeted HAL.",
			"‘Good morning, Frank,’ greeted HAL."
		);
	}

	public function testDoubleQuotesEnclosingTextWithNoLineBreakAreConvertedToQuotationMarks()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'"Good morning, Frank," greeted HAL.',
			'“Good morning, Frank,” greeted HAL.'
		);
	}

	public function testThreeConsecutiveDotsAreConvertedToAnEllipsis()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'Hello world...',
			'Hello world…'
		);
	}

	public function testTwoConsecutiveHypensAreConvertedToAnEnDash()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'foo--bar',
			'foo–bar'
		);
	}

	public function testThreeConsecutiveHypensAreConvertedToAnEmDash()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'foo---bar',
			'foo—bar'
		);
	}

	public function testParenthesesAroundTheLettersTmInLowercaseAreReplacedWithTheTrademarkSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(tm)',
			'™'
		);
	}

	public function testParenthesesAroundTheLettersTmInUppercaseAreReplacedWithTheTrademarkSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(TM)',
			'™'
		);
	}

	public function testParenthesesAroundTheLetterCInLowercaseAreReplacedWithTheCopyrightSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(c)',
			'©'
		);
	}

	public function testParenthesesAroundTheLetterCInUppercaseAreReplacedWithTheCopyrightSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(C)',
			'©'
		);
	}

	public function testParenthesesAroundTheLetterRInLowercaseAreReplacedWithTheRegisteredSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(r)',
			'®'
		);
	}

	public function testParenthesesAroundTheLetterRInUppercaseAreReplacedWithTheRegisteredSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(R)',
			'®'
		);
	}

	public function testASingleQuoteBeforeALetterAtTheStartOfALineIsReplacedWithAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"'Twas the night.\n'Twas the night before Christmas.",
			"’Twas the night.\n’Twas the night before Christmas."
		);
	}


	public function testASingleQuoteBetweenTwoLettersIsReplacedWithAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"Occam's razor",
			"Occam’s razor"
		);
	}

	public function testASingleQuoteBeforeATwoDigitsNumberAtTheStartOfALineIsReplacedWithAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');
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
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"'88 bottles of beer on the wall'",
			"‘88 bottles of beer on the wall’"
		);
	}

	public function testASingleQuoteAfterADigitAndBeforeTheLetterSIsReplacedWithAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"1950's",
			"1950’s"
		);
	}

	public function testASingleQuoteAfterADigitIsReplacedWithAPrime()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"I am 7' tall",
			"I am 7′ tall"
		);
	}

	public function testADoubleQuoteAfterADigitIsReplacedWithADoublePrime()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'12" vynil',
			'12″ vynil'
		);
	}

	public function testTheLetterXPrecededByANumberAndFollowedByANumberIsReplacedWithAMultiplicationSign()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'3x3',
			'3×3'
		);
	}

	public function testTheLetterXPrecededByANumberAndWhitespaceAndFollowedByWhitespaceAndANumberIsReplacedWithAMultiplicationSign()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'3 x 3',
			'3 × 3'
		);
	}

	/**
	* @depends testADoubleQuoteAfterADigitIsReplacedWithADoublePrime
	* @depends testTheLetterXPrecededByANumberAndWhitespaceAndFollowedByWhitespaceAndANumberIsReplacedWithAMultiplicationSign
	*/
	public function testTheLetterXBetweenNumbersWithPrimesIsReplacedWithAMultiplicationSign()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'3" x 3"',
			'3″ × 3″'
		);
	}

	public function testDoesNotAttemptToCreateItsTagIfItAlreadyExists()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->cb->loadPlugin('WittyPants');
	}

	/**
	* @depends testThreeConsecutiveDotsAreConvertedToAnEllipsis
	*/
	public function testTagNameCanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('WittyPants', null, array('tagName' => 'XYZ'));

		$this->assertTransformation(
			'Hello world...',
			'<rt>Hello world<XYZ char="…">...</XYZ></rt>',
			'Hello world…'
		);
	}

	/**
	* @depends testThreeConsecutiveDotsAreConvertedToAnEllipsis
	*/
	public function testAttributeNameCanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('WittyPants', null, array('attrName' => 'xyz'));

		$this->assertTransformation(
			'Hello world...',
			'<rt>Hello world<WP xyz="…">...</WP></rt>',
			'Hello world…'
		);
	}
}