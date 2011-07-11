<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\WittyPantsParser
*/
class WittyPantsParserTest extends Test
{
	/**
	* @test
	*/
	public function Can_use_a_custom_tagName()
	{
		$this->cb->loadPlugin('WittyPants', null, array('tagName' => 'XYZ'));

		$this->assertTransformation(
			'...',
			'<rt><XYZ char="…">...</XYZ></rt>',
			'…'
		);
	}

	/**
	* @test
	*/
	public function Can_use_a_custom_attrName()
	{
		$this->cb->loadPlugin('WittyPants', null, array('attrName' => 'xyz'));

		$this->assertTransformation(
			'...',
			'<rt><WP xyz="…">...</WP></rt>',
			'…'
		);
	}

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

	/**
	* @testdox (tm) is replaced by the trademark symbol
	*/
	public function testParenthesesAroundTheLettersTmInLowercaseAreReplacedWithTheTrademarkSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(tm)',
			'™'
		);
	}

	/**
	* @testdox (TM) is replaced by the trademark symbol
	*/
	public function testParenthesesAroundTheLettersTmInUppercaseAreReplacedWithTheTrademarkSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(TM)',
			'™'
		);
	}

	/**
	* @testdox (c) is replaced by the copyright symbol
	*/
	public function testParenthesesAroundTheLetterCInLowercaseAreReplacedWithTheCopyrightSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(c)',
			'©'
		);
	}

	/**
	* @testdox (C) is replaced by the copyright symbol
	*/
	public function testParenthesesAroundTheLetterCInUppercaseAreReplacedWithTheCopyrightSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(C)',
			'©'
		);
	}

	/**
	* @testdox (r) is replaced by the registered symbol
	*/
	public function testParenthesesAroundTheLetterRInLowercaseAreReplacedWithTheRegisteredSymbol()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			'(r)',
			'®'
		);
	}

	/**
	* @testdox (R) is replaced by the registered symbol
	*/
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

	public function testASingleQuoteBeforeALetterAfterWhitespaceIsReplacedWithAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"Say. 'Twas the night before Christmas.",
			"Say. ’Twas the night before Christmas."
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

	public function testASingleQuoteBetweenALettersAndWhitespaceIsReplacedWithAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"Ridin' dirty",
			"Ridin’ dirty"
		);
	}

	public function testASingleQuoteAfterALettersAtTheEndOfTheTextIsReplacedWithAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"Get rich or die tryin'",
			"Get rich or die tryin’"
		);
	}

	public function testASingleQuoteBetweenALettersAndPunctuationIsReplacedWithAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"Get rich or die tryin', yo.",
			"Get rich or die tryin’, yo."
		);
	}

	public function testASingleQuoteBeforeTwoDigitsAtTheStartOfALineIsReplacedWithAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"'88 was the year.\n'88 was the year indeed.",
			"’88 was the year.\n’88 was the year indeed."
		);
	}

	public function testASingleQuoteBeforeTwoDigitsAfterSomeWhistespaceIsReplacedWithAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');
		$this->assertRendering(
			"'88 was the year. '88 was the year indeed.",
			"’88 was the year. ’88 was the year indeed."
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

	public function testASingleQuoteBetweenTwoLettersIsConvertedToAnApostrophe()
	{
		$this->cb->loadPlugin('WittyPants');

		$this->assertTransformation(
			"O'Connor's pants",
			'<rt>O<WP char="&#x2019;">\'</WP>Connor<WP char="&#x2019;">\'</WP>s pants</rt>',
			'O’Connor’s pants'
		);
	}
}