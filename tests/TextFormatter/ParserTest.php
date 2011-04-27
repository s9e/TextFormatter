<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\PluginConfig,
    s9e\Toolkit\TextFormatter\PluginParser;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Parser
*/
class ParserTest extends Test
{
	public function setUp()
	{
		parent::setUp();
		include_once __DIR__ . '/../../src/TextFormatter/Parser.php';
	}

	protected function assertAttributeIsValid($attrConf, $attrVal, $expectedVal = null, $expectedLog = array())
	{
		$this->assertAttributeValidity($attrConf, $attrVal, $expectedVal, true, $expectedLog);
	}

	protected function assertAttributeIsInvalid($attrConf, $attrVal, $expectedVal = null, $expectedLog = array())
	{
		$this->assertAttributeValidity($attrConf, $attrVal, $expectedVal, false, $expectedLog);
	}

	protected function assertAttributeValidity($attrConf, $attrVal, $expectedVal, $valid, $expectedLog)
	{
		if (!is_array($attrConf))
		{
			$attrConf = array('type' => $attrConf);
		}

		$filtersConfig = $this->cb->getFiltersConfig();

		if (!isset($filtersConfig[$attrConf['type']]))
		{
			$filtersConfig[$attrConf['type']] = array();
		}

		$actualVal = Parser::filter(
			$attrVal,
			$attrConf,
			$filtersConfig[$attrConf['type']],
			$this->parser
		);

		if ($valid)
		{
			if (!isset($expectedVal))
			{
				$expectedVal = $attrVal;
			}

			$this->assertEquals($expectedVal, $actualVal);
		}
		else
		{
			$this->assertFalse($actualVal, 'Invalid attrVal did not return false');
		}

		$this->assertArrayMatches($expectedLog, $this->parser->getLog());
	}

	//==========================================================================
	// Rules
	//==========================================================================

	/**
	* @test
	*/
	public function Fulfilled_requireParent_rule_allows_tag()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a][b]stuff[/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st>stuff<et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	/**
	* @test
	* @depends Fulfilled_requireParent_rule_allows_tag
	*/
	public function requireParent_rule_with_multiple_targets_is_fulfilled_if_any_of_the_targets_is_the_parent()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->BBCodes->addBBCode('c');
		$this->cb->addTagRule('b', 'requireParent', 'a');
		$this->cb->addTagRule('b', 'requireParent', 'c');

		$this->assertParsing(
			'[a][b]stuff[/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st>stuff<et>[/b]</et></B><et>[/a]</et></A></rt>'
		);

		$this->assertParsing(
			'[c][b]stuff[/b][/c]',
			'<rt><C><st>[c]</st><B><st>[b]</st>stuff<et>[/b]</et></B><et>[/c]</et></C></rt>'
		);
	}

	/**
	* @test
	*/
	public function Fulfilled_requireParent_rule_allows_tag_despite_BBCode_suffix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a:123][b]stuff[/b][/a:123]',
			'<rt><A><st>[a:123]</st><B><st>[b]</st>stuff<et>[/b]</et></B><et>[/a:123]</et></A></rt>'
		);
	}

	/**
	* @test
	*/
	public function Unfulfilled_requireParent_rule_blocks_tag()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[b]stuff[/b]',
			'<pt>[b]stuff[/b]</pt>',
			array(
				'error' => array(
					array(
						'msg'     => 'Tag %1$s requires %2$s as parent',
						'params'  => array('B', 'A')
					)
				)
			)
		);
	}

	/**
	* @test
	*/
	public function Unfulfilled_requireParent_rule_blocks_tag_despite_ascendant()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->BBCodes->addBBCode('c');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a][c][b]stuff[/b][/c][/a]',
			'<rt><A><st>[a]</st><C><st>[c]</st>[b]stuff[/b]<et>[/c]</et></C><et>[/a]</et></A></rt>',
			array(
				'error' => array(
					array(
						'msg'     => 'Tag %1$s requires %2$s as parent',
						'params'  => array('B', 'A')
					)
				)
			)
		);
	}

	/**
	* @test
	*/
	public function closeParent_rule_is_enforced()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p]one[p]two',
			'<rt><P><st>[p]</st>one</P><P><st>[p]</st>two</P></rt>'
		);
	}

	/**
	* @test
	* @depends closeParent_rule_is_enforced
	*/
	public function closeParent_rule_is_enforced_on_tag_with_identical_suffix()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p:123]one[p:123]two',
			'<rt><P><st>[p:123]</st>one</P><P><st>[p:123]</st>two</P></rt>'
		);
	}

	/**
	* @test
	* @depends closeParent_rule_is_enforced
	*/
	public function closeParent_rule_is_enforced_on_tag_with_different_suffix()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p:123]one[p:456]two',
			'<rt><P><st>[p:123]</st>one</P><P><st>[p:456]</st>two</P></rt>'
		);
	}

	/**
	* @test
	*/
	public function deny_rule_blocks_tag()
	{
		$this->cb->BBCodes->addBBCode('a', array('defaultRule' => 'allow'));
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('a', 'deny', 'b');

		$this->assertParsing(
			'[a]..[b][/b]..[/a]',
			'<rt><A><st>[a]</st>..[b][/b]..<et>[/a]</et></A></rt>'
		);
	}

	/**
	* @test
	*/
	public function allow_rule_allows_tag()
	{
		$this->cb->BBCodes->addBBCode('a', array('defaultRule' => 'deny'));
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('a', 'allow', 'b');

		$this->assertParsing(
			'[a][b][/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	/**
	* @test
	*/
	public function requireAscendant_rule_is_fulfilled_by_parent()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a][b][/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	/**
	* @test
	* @depends requireAscendant_rule_is_fulfilled_by_parent
	*/
	public function requireAscendant_rule_is_fulfilled_by_parent_with_suffix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a:123][b][/b][/a:123]',
			'<rt><A><st>[a:123]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a:123]</et></A></rt>'
		);
	}

	/**
	* @test
	*/
	public function requireAscendant_rule_is_fulfilled_by_ascendant()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->BBCodes->addBBCode('c');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a][c][b][/b][/c][/a]',
			'<rt><A><st>[a]</st><C><st>[c]</st><B><st>[b]</st><et>[/b]</et></B><et>[/c]</et></C><et>[/a]</et></A></rt>'
		);
	}

	/**
	* @test
	* @depends requireAscendant_rule_is_fulfilled_by_ascendant
	*/
	public function requireAscendant_rule_is_fulfilled_by_ascendant_with_suffix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->BBCodes->addBBCode('c');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a:123][c][b][/b][/c][/a:123]',
			'<rt><A><st>[a:123]</st><C><st>[c]</st><B><st>[b]</st><et>[/b]</et></B><et>[/c]</et></C><et>[/a:123]</et></A></rt>'
		);
	}

	/**
	* @test
	*/
	public function Unfulfilled_requireAscendant_rule_blocks_tag()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[b]stuff[/b]',
			'<pt>[b]stuff[/b]</pt>',
			array(
				'error' => array(
					array(
						'msg'     => 'Tag %1$s requires %2$s as ascendant',
						'params'  => array('B', 'A')
					)
				)
			)
		);
	}

	//==========================================================================
	// Filters
	//==========================================================================

	// Start of content generated by ../../scripts/patchTextFormatterParserTest.php
	public function testIntFilterAcceptsWholeNumbers()
	{
		$this->assertAttributeIsValid('int', '123');
	}

	public function testIntFilterRejectsPartialNumbers()
	{
		$this->assertAttributeIsInvalid('int', '123abc');
	}

	public function testIntFilterAcceptsIntegers()
	{
		$this->assertAttributeIsValid('int', 123);
	}

	public function testIntFilterRejectsNumbersThatStartWithAZero()
	{
		$this->assertAttributeIsInvalid('int', '0123');
	}

	public function testIntFilterRejectsNumbersInScientificNotation()
	{
		$this->assertAttributeIsInvalid('int', '12e3');
	}

	public function testIntFilterAcceptsNegativeNumbers()
	{
		$this->assertAttributeIsValid('int', '-123');
	}

	public function testIntFilterRejectsDecimalNumbers()
	{
		$this->assertAttributeIsInvalid('int', '12.3');
	}

	public function testIntFilterRejectsFloats()
	{
		$this->assertAttributeIsInvalid('int', 12.3);
	}

	public function testIntFilterRejectsNumbersTooBigForThePhpIntegerType()
	{
		$this->assertAttributeIsInvalid('int', '9999999999999999999');
	}

	public function testIntFilterRejectsNumbersInHexNotation()
	{
		$this->assertAttributeIsInvalid('int', '0x123');
	}

	public function testIntegerFilterAcceptsWholeNumbers()
	{
		$this->assertAttributeIsValid('integer', '123');
	}

	public function testIntegerFilterRejectsPartialNumbers()
	{
		$this->assertAttributeIsInvalid('integer', '123abc');
	}

	public function testIntegerFilterAcceptsIntegers()
	{
		$this->assertAttributeIsValid('integer', 123);
	}

	public function testIntegerFilterRejectsNumbersThatStartWithAZero()
	{
		$this->assertAttributeIsInvalid('integer', '0123');
	}

	public function testIntegerFilterRejectsNumbersInScientificNotation()
	{
		$this->assertAttributeIsInvalid('integer', '12e3');
	}

	public function testIntegerFilterAcceptsNegativeNumbers()
	{
		$this->assertAttributeIsValid('integer', '-123');
	}

	public function testIntegerFilterRejectsDecimalNumbers()
	{
		$this->assertAttributeIsInvalid('integer', '12.3');
	}

	public function testIntegerFilterRejectsFloats()
	{
		$this->assertAttributeIsInvalid('integer', 12.3);
	}

	public function testIntegerFilterRejectsNumbersTooBigForThePhpIntegerType()
	{
		$this->assertAttributeIsInvalid('integer', '9999999999999999999');
	}

	public function testIntegerFilterRejectsNumbersInHexNotation()
	{
		$this->assertAttributeIsInvalid('integer', '0x123');
	}

	public function testUintFilterAcceptsWholeNumbers()
	{
		$this->assertAttributeIsValid('uint', '123');
	}

	public function testUintFilterRejectsPartialNumbers()
	{
		$this->assertAttributeIsInvalid('uint', '123abc');
	}

	public function testUintFilterAcceptsIntegers()
	{
		$this->assertAttributeIsValid('uint', 123);
	}

	public function testUintFilterRejectsNumbersThatStartWithAZero()
	{
		$this->assertAttributeIsInvalid('uint', '0123');
	}

	public function testUintFilterRejectsNumbersInScientificNotation()
	{
		$this->assertAttributeIsInvalid('uint', '12e3');
	}

	public function testUintFilterRejectsNegativeNumbers()
	{
		$this->assertAttributeIsInvalid('uint', '-123');
	}

	public function testUintFilterRejectsDecimalNumbers()
	{
		$this->assertAttributeIsInvalid('uint', '12.3');
	}

	public function testUintFilterRejectsFloats()
	{
		$this->assertAttributeIsInvalid('uint', 12.3);
	}

	public function testUintFilterRejectsNumbersTooBigForThePhpIntegerType()
	{
		$this->assertAttributeIsInvalid('uint', '9999999999999999999');
	}

	public function testUintFilterRejectsNumbersInHexNotation()
	{
		$this->assertAttributeIsInvalid('uint', '0x123');
	}

	public function testNumberFilterAcceptsWholeNumbers()
	{
		$this->assertAttributeIsValid('number', '123');
	}

	public function testNumberFilterRejectsPartialNumbers()
	{
		$this->assertAttributeIsInvalid('number', '123abc');
	}

	public function testNumberFilterAcceptsIntegers()
	{
		$this->assertAttributeIsValid('number', 123);
	}

	public function testNumberFilterAcceptsNumbersThatStartWithAZero()
	{
		$this->assertAttributeIsValid('number', '0123');
	}

	public function testNumberFilterRejectsNumbersInScientificNotation()
	{
		$this->assertAttributeIsInvalid('number', '12e3');
	}

	public function testNumberFilterRejectsNegativeNumbers()
	{
		$this->assertAttributeIsInvalid('number', '-123');
	}

	public function testNumberFilterRejectsDecimalNumbers()
	{
		$this->assertAttributeIsInvalid('number', '12.3');
	}

	public function testNumberFilterRejectsFloats()
	{
		$this->assertAttributeIsInvalid('number', 12.3);
	}

	public function testNumberFilterAcceptsNumbersTooBigForThePhpIntegerType()
	{
		$this->assertAttributeIsValid('number', '9999999999999999999');
	}

	public function testNumberFilterRejectsNumbersInHexNotation()
	{
		$this->assertAttributeIsInvalid('number', '0x123');
	}

	public function testFloatFilterAcceptsWholeNumbers()
	{
		$this->assertAttributeIsValid('float', '123');
	}

	public function testFloatFilterRejectsPartialNumbers()
	{
		$this->assertAttributeIsInvalid('float', '123abc');
	}

	public function testFloatFilterAcceptsIntegers()
	{
		$this->assertAttributeIsValid('float', 123);
	}

	public function testFloatFilterAcceptsNumbersThatStartWithAZero()
	{
		$this->assertAttributeIsValid('float', '0123', '123');
	}

	public function testFloatFilterAcceptsNumbersInScientificNotation()
	{
		$this->assertAttributeIsValid('float', '12e3', '12000');
	}

	public function testFloatFilterAcceptsNegativeNumbers()
	{
		$this->assertAttributeIsValid('float', '-123');
	}

	public function testFloatFilterAcceptsDecimalNumbers()
	{
		$this->assertAttributeIsValid('float', '12.3');
	}

	public function testFloatFilterAcceptsFloats()
	{
		$this->assertAttributeIsValid('float', 12.3);
	}

	public function testFloatFilterAcceptsNumbersTooBigForThePhpIntegerType()
	{
		$this->assertAttributeIsValid('float', '9999999999999999999', '1.0E+19');
	}

	public function testFloatFilterRejectsNumbersInHexNotation()
	{
		$this->assertAttributeIsInvalid('float', '0x123');
	}
	// End of content generated by ../../scripts/patchTextFormatterParserTest.php

	public function testInvalidUrlsAreRejected()
	{
		$this->assertAttributeIsInvalid('url', 'invalid');
	}

	public function testUrlsWithNoHostAreRejected()
	{
		$this->assertAttributeIsInvalid('url', '/path/to/file');
	}

	public function testUrlsWithNoPathAreAccepted()
	{
		$this->assertAttributeIsValid('url', 'http://www.example.com');
	}

	public function testUrlFilterRejectsNotAllowedSchemes()
	{
		$this->assertAttributeIsInvalid(
			'url',
			'ftp://www.example.com',
			null,
			array(
				'error' => array(
					array(
						'msg'    => "URL scheme '%s' is not allowed",
						'params' => array('ftp')
					)
				)
			)
		);
	}

	public function testUrlFilterCanAcceptNonHttpSchemes()
	{
		$this->cb->allowScheme('ftp');

		$this->assertAttributeIsValid('url', 'ftp://www.example.com');
	}

	public function testUrlFilterRejectsDisallowedHost()
	{
		$this->cb->disallowHost('evil.example.com');

		$this->assertAttributeIsInvalid(
			'url',
			'http://evil.example.com',
			null,
			array(
				'error' => array(
					array(
						'msg'    => "URL host '%s' is not allowed",
						'params' => array('evil.example.com')
					)
				)
			)
		);
	}

	public function testUrlFilterRejectsDisallowedHostMask()
	{
		$this->cb->disallowHost('*.example.com');

		$this->assertAttributeIsInvalid(
			'url',
			'http://evil.example.com',
			null,
			array(
				'error' => array(
					array(
						'msg'    => "URL host '%s' is not allowed",
						'params' => array('evil.example.com')
					)
				)
			)
		);
	}

	public function testUrlFilterRejectsSubdomains()
	{
		$this->cb->disallowHost('example.com');

		$this->assertAttributeIsInvalid(
			'url',
			'http://evil.example.com',
			null,
			array(
				'error' => array(
					array(
						'msg'    => "URL host '%s' is not allowed",
						'params' => array('evil.example.com')
					)
				)
			)
		);
	}

	public function testUrlFilterRejectsDisallowedTld()
	{
		$this->cb->disallowHost('*.com');

		$this->assertAttributeIsInvalid(
			'url',
			'http://evil.example.com',
			null,
			array(
				'error' => array(
					array(
						'msg'    => "URL host '%s' is not allowed",
						'params' => array('evil.example.com')
					)
				)
			)
		);
	}

	public function testUrlFilterDoesNotRejectHostOnPartialMatch()
	{
		$this->cb->disallowHost('example.com');

		$this->assertAttributeIsValid('url', 'http://anotherexample.com');
	}

	public function testUrlFilterRejectsPseudoSchemes()
	{
		$this->assertAttributeIsInvalid('url', 'javascript:alert(\'@http://www.com\')');
	}

	public function testIdFilterAcceptsNumbers()
	{
		$this->assertAttributeIsValid('id', '123');
	}

	public function testIdFilterAcceptsLowercaseLetters()
	{
		$this->assertAttributeIsValid('id', 'abc');
	}

	public function testIdFilterAcceptsUppercaseLetters()
	{
		$this->assertAttributeIsValid('id', 'ABC');
	}

	public function testIdFilterAcceptsDashes()
	{
		$this->assertAttributeIsValid('id', '---');
	}

	public function testIdFilterAcceptsUnderscores()
	{
		$this->assertAttributeIsValid('id', '___');
	}

	public function testIdFilterRejectsSpaces()
	{
		$this->assertAttributeIsInvalid('id', '123 abc');
	}

	public function testIdentifierFilterIsAnAliasForTheIdFilter()
	{
		$this->assertAttributeIsValid('id', '-123abc_XYZ');
	}

	public function testColorFilterAcceptsRgbHexValues()
	{
		$this->assertAttributeIsValid('color', '#123abc');
	}

	public function testColorFilterRejectsInvalidRgbHexValues()
	{
		$this->assertAttributeIsInvalid('color', '#1234567');
	}

	public function testColorFilterAcceptsValuesMadeEntirelyOfLetters()
	{
		$this->assertAttributeIsValid('color', 'blueish');
	}

	public function testRangeFilterAllowsIntegersWithinRange()
	{
		$this->assertAttributeIsValid(
			array(
				'type' => 'range',
				'min'  => 5,
				'max'  => 10
			),
			8
		);
	}

	public function testRangeFilterAllowsNegativeIntegersWithinRange()
	{
		$this->assertAttributeIsValid(
			array(
				'type' => 'range',
				'min'  => -5,
				'max'  => 10
			),
			8
		);
	}

	public function testRangeFilterRejectsDecimalNumbers()
	{
		$this->assertAttributeIsInvalid(
			array(
				'type' => 'range',
				'min'  => 5,
				'max'  => 10
			),
			8.4
		);
	}

	public function testRangeFilterAdjustsValuesBelowRange()
	{
		$this->assertAttributeIsValid(
			array(
				'type' => 'range',
				'min'  => 5,
				'max'  => 10
			),
			3,
			5,
			array(
				'warning' => array(
					array(
						'msg' => 'Value outside of range, adjusted up to %d',
						'params' => array(5)
					)
				)
			)
		);
	}

	public function testRangeFilterAdjustsValuesAboveRange()
	{
		$this->assertAttributeIsValid(
			array(
				'type' => 'range',
				'min'  => 5,
				'max'  => 10
			),
			30,
			10,
			array(
				'warning' => array(
					array(
						'msg' => 'Value outside of range, adjusted down to %d',
						'params' => array(10)
					)
				)
			)
		);
	}

	public function testSimpletextFilterAcceptsLettersNumbersMinusAndPlusSignsDotsCommasUnderscoresAndSpaces()
	{
		$this->assertAttributeIsValid(
			'simpletext',
			'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+.,_ '
		);
	}

	public function testSimpletextFilterRejectsEverythingElse()
	{
		$allowed = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+.,_ ';

		for ($i = 32; $i <= 255; ++$i)
		{
			$c = chr($i);

			if (strpos($allowed, $c) === false)
			{
				$this->assertAttributeIsInvalid('simpletext', utf8_encode($c));
			}
		}
	}

	public function testRegexpFilterAcceptsContentThatMatches()
	{
		$this->assertAttributeIsValid(
			array('type' => 'regexp', 'regexp' => '#^[A-Z]$#D'),
			'J'
		);
	}

	public function testRegexpFilterRejectsContentThatDoesNotMatch()
	{
		$this->assertAttributeIsInvalid(
			array('type' => 'regexp', 'regexp' => '#^[A-Z]$#D'),
			'8'
		);
	}

	public function testRegexpFilterReplacesContentWithThePatternFoundInReplaceIfValid()
	{
		$this->assertAttributeIsValid(
			array('type' => 'regexp', 'regexp' => '#^([A-Z])$#D', 'replace' => 'x$1x'),
			'J',
			'xJx'
		);
	}

	public function testRegexpFilterDoesNotReplaceContentWithThePatternFoundInReplaceIfInvalid()
	{
		$this->assertAttributeIsInvalid(
			array('type' => 'regexp', 'regexp' => '#^([A-Z])$#D', 'replace' => 'x$1x'),
			'8'
		);
	}

	public function testRegexpFilterCorrectlyHandlesBackslashesInReplacePattern()
	{
		/**
		* Here we have the $2 token, followed by the literal "$2" followed by the $1 token
		* followed by the literal "\" (one backslash) followed by the $1 token followed by
		* the literal "\$1" (one backslash then dollar sign then 1) followed by the literal
		* "\\" (two backslashes)
		*
		* The result should be R$2L\L\$1\\
		*/
		$replace = '$2\\$2$1\\\\$1\\\\\\$1\\\\\\\\';
		$this->assertAttributeIsValid(
			array('type' => 'regexp', 'regexp' => '#^(L)(R)$#D', 'replace' => $replace),
			'LR',
			'R$2L\\L\\$1\\\\'
		);
	}

	public function testEmailFilterAcceptsValidEmails()
	{
		$this->assertAttributeIsValid('email', 'example@example.com');
	}

	public function testEmailFilterRejectsInvalidEmails()
	{
		$this->assertAttributeIsInvalid('email', 'example@example.com?');
	}

	public function testEmailFilterCanUrlencodeEveryCharacterOfAValidEmailIfForceUrlencodeIsOn()
	{
		$this->assertAttributeIsValid(
			array('type' => 'email', 'forceUrlencode' => true),
			'example@example.com',
			'%65%78%61%6d%70%6c%65%40%65%78%61%6d%70%6c%65%2e%63%6f%6d'
		);
	}

	public function testEmailFilterWillNotUrlencodeAnInvalidEmailEvenIfForceUrlencodeIsOn()
	{
		$this->assertAttributeIsInvalid(
			array('type' => 'email', 'forceUrlencode' => true),
			'example@invalid?'
		);
	}

	public function testUndefinedFilterRejectsEverything()
	{
		$this->assertAttributeIsInvalid(
			'whoknows',
			'foobar',
			null,
			array(
				'debug' => array(
					array(
						'msg'    => "Unknown filter '%s'",
						'params' => array('whoknows')
					)
				)
			)
		);
	}

	//==========================================================================
	// Attributes stuff
	//==========================================================================

	public function testAttributeNameIsAddedToLogEntriesWhenAvailable()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'y', 'mytype');

		$this->cb->setFilter(
			'mytype',
			array(
				'params' => array('parser' => null),
				'callback' =>
					function($parser)
					{
						$parser->log('error', array('msg' => 'mytype error'));
						return false;
					}
			)
		);

		$this->cb->Canned->tags[] = array(
			'pos'   => 1,
			'len'   => 0,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG,
			'attrs' => array('y' => 0)
		);

		$this->assertParsing(
			'text',
			'<pt>text</pt>',
			array(
				'error' => array(
					array(
						'msg' => 'mytype error',
						'pos' => 1,
						'tagName'  => 'X',
						'attrName' => 'y'
					)
				)
			)
		);
	}

	/**
	* @test
	*/
	public function Tag_level_preFilter_callback_receives_an_associative_array_of_attributes_which_gets_replaced_by_its_return_value()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X', array(
			'preFilter' => array(
				array(
					'callback' =>
						function($attrs)
						{
							// add an attribute
							$attrs['z'] = 'zval';

							return array_map('strtoupper', $attrs);
						}
				)
			)
		));
		$this->cb->addTagAttribute('X', 'x', 'text');
		$this->cb->addTagAttribute('X', 'y', 'text');
		$this->cb->addTagAttribute('X', 'z', 'text');

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 1,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG,
			'attrs' => array(
				'x' => 'xval',
				'y' => 'yval'
			)
		);

		$this->assertParsing(
			'.',
			'<rt><X x="XVAL" y="YVAL" z="ZVAL">.</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Tag_level_postFilter_callback_receives_an_associative_array_of_attributes_which_gets_replaced_by_its_return_value()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X', array(
			'postFilter' => array(
				array(
					'callback' =>
						function($attrs)
						{
							// add an attribute
							$attrs['z'] = 'zval';

							return array_map('strtoupper', $attrs);
						}
				)
			)
		));
		$this->cb->addTagAttribute('X', 'x', 'text');
		$this->cb->addTagAttribute('X', 'y', 'text');
		$this->cb->addTagAttribute('X', 'z', 'text');

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 1,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG,
			'attrs' => array(
				'x' => 'xval',
				'y' => 'yval'
			)
		);

		$this->assertParsing(
			'.',
			'<rt><X x="XVAL" y="YVAL" z="ZVAL">.</X></rt>'
		);
	}

	public function testCompoundAttributesAreSplitIfValidThenRemoved()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'x', 'int', array('isRequired' => false));
		$this->cb->addTagAttribute('X', 'y', 'int', array('isRequired' => false));
		$this->cb->addTagAttribute('X', 'xy', 'compound', array(
			'regexp' => '#^(?P<x>[0-9]+),(?P<y>[0-9]+)$#D'
		));

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 1,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG,
			'attrs' => array('xy' => '123,456')
		);

		$this->assertParsing(
			'.',
			'<rt><X x="123" y="456">.</X></rt>'
		);
	}

	/**
	* @depends testCompoundAttributesAreSplitIfValidThenRemoved
	*/
	public function testCompoundAttributesDoNotOverwriteExistingValues()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'x', 'int', array('isRequired' => false));
		$this->cb->addTagAttribute('X', 'y', 'int', array('isRequired' => false));
		$this->cb->addTagAttribute('X', 'xy', 'compound', array(
			'regexp' => '#^(?P<x>[0-9]+),(?P<y>[0-9]+)$#D'
		));

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 1,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG,
			'attrs' => array('xy' => '123,456', 'x' => 999)
		);

		$this->assertParsing(
			'.',
			'<rt><X x="999" y="456">.</X></rt>'
		);
	}

	public function testCompoundAttributesAreRemovedIfInvalid()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'x', 'int', array('isRequired' => false));
		$this->cb->addTagAttribute('X', 'y', 'int', array('isRequired' => false));
		$this->cb->addTagAttribute('X', 'xy', 'compound', array(
			'regexp' => '#^(?P<x>[0-9]+),(?P<y>[0-9]+)$#D',
			'isRequired' => false
		));

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 1,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG,
			'attrs' => array('xy' => 'invalid')
		);

		$this->assertParsing(
			'.',
			'<rt><X>.</X></rt>'
		);
	}

	/**
	* @depends testCompoundAttributesAreRemovedIfInvalid
	*/
	public function testCompoundAttributesAreOptionalByDefault()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'x', 'int', array('isRequired' => false));
		$this->cb->addTagAttribute('X', 'y', 'int', array('isRequired' => false));
		$this->cb->addTagAttribute('X', 'xy', 'compound', array(
			'regexp' => '#^(?P<x>[0-9]+),(?P<y>[0-9]+)$#D'
		));

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 1,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG,
			'attrs' => array('xy' => 'invalid')
		);

		$this->assertParsing(
			'.',
			'<rt><X>.</X></rt>'
		);
	}

	public function testInvalidAttributesUseTheirDefaultValueIfSet()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'x', 'int', array('isRequired' => false, 'default' => 42));

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 1,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG,
			'attrs' => array('x' => 'invalid')
		);

		$this->assertParsing(
			'.',
			'<rt><X x="42">.</X></rt>',
			array(
				'error' => array(
					array(
						'msg'    => "Invalid attribute '%s'",
						'params' => array('x')
					)
				)
			)
		);
	}

	public function testMissingAttributesUseTheirDefaultValueIfSet()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'x', 'int', array('isRequired' => false, 'default' => 42));

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 1,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG
		);

		$this->assertParsing(
			'.',
			'<rt><X x="42">.</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attribute_value_is_replaced_by_the_return_value_of_the_attribute_preFilter_callbacks()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'x', 'int', array('preFilter' => array(
			array('callback' => function($attrVal) { return 42; })
		)));

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 1,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG,
			'attrs' => array('x' => 'invalid')
		);

		$this->assertParsing(
			'.',
			'<rt><X x="42">.</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attribute_value_is_replaced_by_the_return_value_of_the_attribute_postFilter_callbacks_even_if_it_makes_it_invalid()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'x', 'int', array('postFilter' => array(
			array('callback' => function($attrVal) { return 'invalid'; })
		)));

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 1,
			'name'  => 'X',
			'type'  => Parser::SELF_CLOSING_TAG,
			'attrs' => array('x' => 42)
		);

		$this->assertParsing(
			'.',
			'<rt><X x="invalid">.</X></rt>'
		);
	}

	//==========================================================================
	// Tags stuff
	//==========================================================================

	public function testPlainTextIsReturnedWithinPtTags()
	{
		$this->assertParsing('plain text', '<pt>plain text</pt>');
	}

	public function testUndefinedAttributesDoNotAppearInXml()
	{
		$this->cb->BBCodes->addBBCode('x');
		$this->assertParsing(
			'[x unknown=123 /]',
			'<rt><X>[x unknown=123 /]</X></rt>'
		);
	}

	public function testOverlappingTagsAreRemoved()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		foreach (array(1, 2, 0, 4) as $pos)
		{
			$this->cb->addTag('X' . $pos);

			$this->cb->Canned->tags[] = array(
				'pos'  => $pos,
				'len'  => 2,
				'name' => 'X' . $pos,
				'type' => Parser::SELF_CLOSING_TAG
			);
		}

		$this->assertParsing(
			'012345',
			'<rt><X0>01</X0><X2>23</X2><X4>45</X4></rt>',
			array(
				'debug' => array(array(
					'msg' => 'Tag skipped',
					'pos' => 1,
					'tagName' => 'X1'
				))
			)
		);
	}

	public function testTagsAreSortedCorrectly()
	{
		$_tb = 0;

		$tags = array();

		foreach (array(0, 1) as $pos)
		{
			// Create zero-width tags around the character
			foreach (array('B', 'I') as $tagName)
			{
				$tags[] = array(
					'name' => $tagName . $pos,
					'pos'  => $pos,
					'len'  => 0,
					'type' => Parser::START_TAG,
					'_tb'  => ++$_tb
				);

				$tags[] = array(
					'name' => $tagName . $pos,
					'pos'  => 1 + $pos,
					'len'  => 0,
					'type' => Parser::END_TAG,
					'_tb'  => ++$_tb
				);
			}

			// Add a self-closing tag that consumes the character
			$tags[] = array(
				'name' => 'E' . $pos,
				'pos'  => $pos,
				'len'  => 1,
				'type' => Parser::SELF_CLOSING_TAG,
				'_tb'  => ++$_tb
			);
		}

		foreach (array(0, 1, 2) as $pos)
		{
			// Add a zero-width self-closing tag at given position
			$tags[] = array(
				'name' => 'Z' . $pos,
				'pos'  => $pos,
				'len'  => 0,
				'type' => Parser::SELF_CLOSING_TAG,
				'_tb'  => ++$_tb
			);
		}

		// sort the tags
		usort($tags, array('s9e\\Toolkit\\TextFormatter\\Parser', 'compareTags'));

		// reverse the order to make it more readable (the sort method is designed for a stack,
		// therefore it sorts tags in reverse order)
		$tags = array_reverse($tags);

		$result = '';
		foreach ($tags as $tag)
		{
			$result .= '<'
			         . (($tag['type'] === Parser::END_TAG) ? '/' : '')
			         . $tag['name']
			         . (($tag['type'] === Parser::SELF_CLOSING_TAG) ? '/' : '')
			         . '>';
		}

		$this->assertSame(
			'<Z0/><B0><I0><E0/></I0></B0><Z1/><B1><I1><E1/></I1></B1><Z2/>',
			$result
		);
	}

	public function testTheNumberOfRegexpMatchesCanBeLimitedWithExtraMatchesIgnored()
	{
		include_once __DIR__ . '/includes/MultiRegexpConfig.php';
		$this->cb->loadPlugin('MultiRegexp', __NAMESPACE__ . '\\MultiRegexpConfig');

		$this->cb->MultiRegexp->regexpLimit = 3;
		$this->cb->MultiRegexp->regexpLimitAction = 'ignore';

		$this->assertParsing(
			'00 00',
			'<rt><X>0</X><X>0</X> <X>0</X>0</rt>',
			array(
				'debug' => array(array(
					'msg' => '%1$s limit exceeded. Only the first %2$s matches will be processed',
					'params' => array('MultiRegexp', 3)
				))
			)
		);
	}

	public function testTheNumberOfRegexpMatchesCanBeLimitedWithExtraMatchesIgnoredAndAWarning()
	{
		include_once __DIR__ . '/includes/MultiRegexpConfig.php';
		$this->cb->loadPlugin('MultiRegexp', __NAMESPACE__ . '\\MultiRegexpConfig');

		$this->cb->MultiRegexp->regexpLimit = 3;
		$this->cb->MultiRegexp->regexpLimitAction = 'warn';

		$this->assertParsing(
			'00 00',
			'<rt><X>0</X><X>0</X> <X>0</X>0</rt>',
			array(
				'warning' => array(array(
					'msg' => '%1$s limit exceeded. Only the first %2$s matches will be processed',
					'params' => array('MultiRegexp', 3)
				))
			)
		);
	}

	public function testTheNumberOfRegexpMatchesCanBeLimitedAcrossMultipleRegexpsWithExtraMatchesIgnored()
	{
		include_once __DIR__ . '/includes/MultiRegexpConfig.php';
		$this->cb->loadPlugin('MultiRegexp', __NAMESPACE__ . '\\MultiRegexpConfig');

		$this->cb->MultiRegexp->regexpLimit = 3;
		$this->cb->MultiRegexp->regexpLimitAction = 'ignore';

		$this->assertParsing(
			'00 11',
			'<rt><X>0</X><X>0</X> <X>1</X>1</rt>',
			array(
				'debug' => array(array(
					'msg' => '%1$s limit exceeded. Only the first %2$s matches will be processed',
					'params' => array('MultiRegexp', 3)
				))
			)
		);
	}

	/**
	* @expectedException RuntimeException
	* @expectedExceptionMessage MultiRegexp limit exceeded
	*/
	public function testTheNumberOfRegexpMatchesCanBeLimitedAndParsingAbortedIfLimitExceeded()
	{
		include_once __DIR__ . '/includes/MultiRegexpConfig.php';
		$this->cb->loadPlugin('MultiRegexp', __NAMESPACE__ . '\\MultiRegexpConfig');

		$this->cb->MultiRegexp->regexpLimit = 3;
		$this->cb->MultiRegexp->regexpLimitAction = 'abort';

		$this->parser->parse('00 00');
	}

	public function testUnknownTagsAreIgnored()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->Canned->tags = array(
			array(
				'pos'  => 0,
				'len'  => 1,
				'name' => 'X',
				'type' => Parser::START_TAG
			)
		);

		$this->assertParsing(
			'00 00',
			'<pt>00 00</pt>',
			array(
				'debug' => array(array(
					'msg' => 'Removed unknown tag %1$s from plugin %2$s',
					'params' => array('X', 'Canned')
				))
			)
		);
	}

	public function testTagsNestingLimitIsEnforced()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X', array('nestingLimit' => 2));

		foreach (array(0, 1, 2) as $pos)
		{
			$this->cb->Canned->tags[] = array(
				'pos'   => $pos,
				'len'   => 1,
				'name'  => 'X',
				'type'  => Parser::START_TAG
			);

			$this->cb->Canned->tags[] = array(
				'pos'   => 5 - $pos,
				'len'   => 1,
				'name'  => 'X',
				'type'  => Parser::END_TAG
			);
		}

		$this->assertParsing(
			'SSSEEE',
			'<rt>
				<X>
					<st>S</st>
					<X>
						<st>S</st>'
						. 'S' .
						'<et>E</et>
					</X>
					<et>E</et>
				</X>'
			. 'E'
			. '</rt>'
		);
	}

	public function testTagsTagLimitIsEnforced()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X', array('tagLimit' => 2));

		foreach (array(0, 1, 2) as $pos)
		{
			$this->cb->Canned->tags[] = array(
				'pos'   => $pos,
				'len'   => 1,
				'name'  => 'X',
				'type'  => Parser::SELF_CLOSING_TAG
			);
		}

		$this->assertParsing(
			'012',
			'<rt><X>0</X><X>1</X>2</rt>'
		);
	}

	public function testZeroWidthTagsAreCorrectlyOuput()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X', array('tagLimit' => 2));

		foreach (array(0, 1, 2) as $pos)
		{
			$this->cb->Canned->tags[] = array(
				'pos'   => $pos,
				'len'   => 0,
				'name'  => 'X',
				'type'  => Parser::SELF_CLOSING_TAG
			);
		}

		$this->assertParsing(
			'012',
			'<rt><X />0<X />12</rt>'
		);
	}

	public function testTagsLeftOpenGetClosedWhenTheirAncestorGetsClosed()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');
		$this->cb->addTag('Y');
		$this->cb->addTag('Z');

		$this->cb->Canned->tags[] = array(
			'pos'   => 0,
			'len'   => 0,
			'name'  => 'X',
			'type'  => Parser::START_TAG
		);

		$this->cb->Canned->tags[] = array(
			'pos'   => 1,
			'len'   => 0,
			'name'  => 'Y',
			'type'  => Parser::START_TAG
		);

		$this->cb->Canned->tags[] = array(
			'pos'   => 2,
			'len'   => 0,
			'name'  => 'Z',
			'type'  => Parser::START_TAG
		);

		$this->cb->Canned->tags[] = array(
			'pos'   => 3,
			'len'   => 0,
			'name'  => 'X',
			'type'  => Parser::END_TAG
		);

		$this->assertParsing(
			'012',
			'<rt><X>0<Y>1<Z>2</Z></Y></X></rt>'
		);
	}

	public function testTagsCanSpecifyAListOfTagsThatAreRequired()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');

		$this->cb->Canned->tags[] = array(
			'pos'  => 0,
			'len'  => 1,
			'name' => 'X',
			'type' => Parser::SELF_CLOSING_TAG
		);

		$this->cb->Canned->tags[] = array(
			'pos'  => 1,
			'len'  => 1,
			'name' => 'X',
			'type' => Parser::SELF_CLOSING_TAG,
			'requires' => array(0)
		);

		$this->cb->Canned->tags[] = array(
			'pos'  => 2,
			'len'  => 1,
			'name' => 'X',
			'type' => Parser::SELF_CLOSING_TAG,
			'requires' => array(0, 1)
		);

		$this->assertParsing(
			'012',
			'<rt><X>0</X><X>1</X><X>2</X></rt>'
		);
	}

	/**
	* @depends testOverlappingTagsAreRemoved
	*/
	public function testTagsCanSpecifyAListOfTagsThatAreRequiredAndBeSkippedIfAnyRequiredTagIsAbsent()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';
		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->cb->addTag('X');

		$this->cb->Canned->tags[] = array(
			'pos'  => 0,
			'len'  => 1,
			'name' => 'X',
			'type' => Parser::SELF_CLOSING_TAG
		);

		$this->cb->Canned->tags[] = array(
			'pos'  => 0,
			'len'  => 1,
			'name' => 'X',
			'type' => Parser::SELF_CLOSING_TAG,
			'requires' => array(0)
		);

		$this->cb->Canned->tags[] = array(
			'pos'  => 2,
			'len'  => 1,
			'name' => 'X',
			'type' => Parser::SELF_CLOSING_TAG,
			'requires' => array(0, 1)
		);

		$this->assertParsing(
			'012',
			'<rt><X>0</X>12</rt>'
		);
	}

	//==========================================================================
	// Whitespace trimming
	//==========================================================================

	/**
	* @dataProvider getWhitespaceTrimming
	*/
	public function testWhitespaceTrimmingWorks($options, $text, $expectedHtml, $expectedXml)
	{
		include_once __DIR__ . '/includes/WhitespaceConfig.php';

		$this->cb->loadPlugin(
			'Whitespace',
			__NAMESPACE__ . '\\WhitespaceConfig',
			array('options' => $options)
		);

		$actualXml = $this->parser->parse($text);
		$this->assertSame($expectedXml, $actualXml);

		$actualHtml = $this->renderer->render($expectedXml);
		$this->assertSame($expectedHtml, $actualHtml);
	}

	public function getWhitespaceTrimming()
	{
		/**
		* The elements, in order:
		*
		* - the BBCode options that are set for the [mark] BBCode
		* - text input
		* - HTML rendering
		* - intermediate representation in XML
		*
		* The tags' templates are set to recreate the tags as shown in the input, e.g. [b] will be
		* rendered as [b].
		*
		* In addition, a special plugin is used in order to use the string "tag" and " tagws " as
		* tags to study the interaction between the space consumed by a tag and the trimming option.
		*/
		return array(
			array(
				array('ltrimContent' => true),
				'[b] [mark] 1 [/mark] 2 [mark] 3 [/mark] [/b]',
				'[b] [mark]1 [/mark] 2 [mark]3 [/mark] [/b]',
				'<rt><B><st>[b]</st> <MARK><st>[mark]</st><i> </i>1 <et>[/mark]</et></MARK> 2 <MARK><st>[mark]</st><i> </i>3 <et>[/mark]</et></MARK> <et>[/b]</et></B></rt>'
			),
			array(
				array('rtrimContent' => true),
				'[b] [mark] 1 [/mark] 2 [mark] 3 [/mark] [/b]',
				'[b] [mark] 1[/mark] 2 [mark] 3[/mark] [/b]',
				'<rt><B><st>[b]</st> <MARK><st>[mark]</st> 1<i> </i><et>[/mark]</et></MARK> 2 <MARK><st>[mark]</st> 3<i> </i><et>[/mark]</et></MARK> <et>[/b]</et></B></rt>'
			),
			array(
				array('ltrimContent' => true, 'rtrimContent' => true),
				'[b] [mark] 1 [/mark] 2 [mark] 3 [/mark] [/b]',
				'[b] [mark]1[/mark] 2 [mark]3[/mark] [/b]',
				'<rt><B><st>[b]</st> <MARK><st>[mark]</st><i> </i>1<i> </i><et>[/mark]</et></MARK> 2 <MARK><st>[mark]</st><i> </i>3<i> </i><et>[/mark]</et></MARK> <et>[/b]</et></B></rt>'
			),
			array(
				array('trimBefore' => true),
				'[b] [mark] 1 [/mark] 2 [mark] 3 [/mark] [/b]',
				'[b][mark] 1 [/mark] 2[mark] 3 [/mark] [/b]',
				'<rt><B><st>[b]</st><i> </i><MARK><st>[mark]</st> 1 <et>[/mark]</et></MARK> 2<i> </i><MARK><st>[mark]</st> 3 <et>[/mark]</et></MARK> <et>[/b]</et></B></rt>'
			),
			array(
				array('trimAfter' => true),
				'[b] [mark] 1 [/mark] 2 [mark] 3 [/mark] [/b]',
				'[b] [mark] 1 [/mark]2 [mark] 3 [/mark][/b]',
				'<rt><B><st>[b]</st> <MARK><st>[mark]</st> 1 <et>[/mark]</et></MARK><i> </i>2 <MARK><st>[mark]</st> 3 <et>[/mark]</et></MARK><i> </i><et>[/b]</et></B></rt>'
			),
			array(
				array('trimBefore' => true, 'trimAfter' => true),
				'[b] [mark] 1 [/mark] 2 [mark] 3 [/mark] [/b]',
				'[b][mark] 1 [/mark]2[mark] 3 [/mark][/b]',
				'<rt><B><st>[b]</st><i> </i><MARK><st>[mark]</st> 1 <et>[/mark]</et></MARK><i> </i>2<i> </i><MARK><st>[mark]</st> 3 <et>[/mark]</et></MARK><i> </i><et>[/b]</et></B></rt>'
			),
			/**
			* In the following examples, the one space around "tagws" will not be removed. This is
			* because the plugin's parser defines it as part of the tag. Therefore, it makes sense
			* to actually preserve it
			*/
			array(
				array('ltrimContent' => true),
				'[b]  tagws  |  tagws  [/b]',
				'[b] [mark] tagws [/mark] | [mark] tagws [/mark] [/b]',
				'<rt><B><st>[b]</st> <MARK> tagws </MARK> | <MARK> tagws </MARK> <et>[/b]</et></B></rt>'
			),
			array(
				array('rtrimContent' => true),
				'[b]  tagws  |  tagws  [/b]',
				'[b] [mark] tagws [/mark] | [mark] tagws [/mark] [/b]',
				'<rt><B><st>[b]</st> <MARK> tagws </MARK> | <MARK> tagws </MARK> <et>[/b]</et></B></rt>'
			),
			array(
				array('trimBefore' => true),
				'[b]  tagws  |  tagws  [/b]',
				'[b][mark] tagws [/mark] |[mark] tagws [/mark] [/b]',
				'<rt><B><st>[b]</st><i> </i><MARK> tagws </MARK> |<i> </i><MARK> tagws </MARK> <et>[/b]</et></B></rt>'
			),
			array(
				array('trimAfter' => true),
				'[b]  tagws  |  tagws  [/b]',
				'[b] [mark] tagws [/mark]| [mark] tagws [/mark][/b]',
				'<rt><B><st>[b]</st> <MARK> tagws </MARK><i> </i>| <MARK> tagws </MARK><i> </i><et>[/b]</et></B></rt>'
			),
			array(
				array('trimBefore' => true),
				'[b] tag | tag [/b]',
				'[b][mark]tag[/mark] |[mark]tag[/mark] [/b]',
				'<rt><B><st>[b]</st><i> </i><MARK>tag</MARK> |<i> </i><MARK>tag</MARK> <et>[/b]</et></B></rt>'
			),
			array(
				array('trimAfter' => true),
				'[b] tag | tag [/b]',
				'[b] [mark]tag[/mark]| [mark]tag[/mark][/b]',
				'<rt><B><st>[b]</st> <MARK>tag</MARK><i> </i>| <MARK>tag</MARK><i> </i><et>[/b]</et></B></rt>'
			)
		);
	}
}