<?php

namespace s9e\Toolkit\Tests;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Parser
*/
class ParserTest extends Test
{
	protected function assertAttributeIsValid($conf, $value, $outputValue = null, $error = null)
	{
		$this->assertAttributeValidity($conf, $value, $outputValue, true, $error);
	}

	protected function assertAttributeIsInvalid($conf, $value, $outputValue = null, $error = null)
	{
		$this->assertAttributeValidity($conf, $value, $outputValue, false, $error);
	}

	protected function assertAttributeValidity($conf, $value, $outputValue, $valid, $error)
	{
		if (!is_array($conf))
		{
			$conf = array('type' => $conf);
		}

		if (!isset($outputValue))
		{
			$outputValue = $value;
		}

		if (!$this->cb->tagExists('X'))
		{
			$this->cb->BBCodes->addBBCode(
				'X',
				array(
					'attrs' => array('attr' => $conf)
				)
			);
		}

		$st = '[X attr="' . addslashes($value) . '"]';
		$et = '[/X]';
		$text = $st . $et;

		if ($valid)
		{
			$rt = simplexml_load_string('<rt><X/></rt>');

			$rt->X['attr'] = $outputValue;
			$rt->X->st = $st;
			$rt->X->et = $et;

			$expectedXml = $rt->asXml();
			$expectedLog = array();
		}
		else
		{
			$root = simplexml_load_string('<root/>');
			$root->pt = $text;

			$expectedXml = $root->pt->asXml();
			$expectedLog =
				array(
					'error' => array(
						array(
							'msg'    => "Invalid attribute '%s'",
							'params' => array('attr')
						)
					)
				);

			if (isset($error))
			{
				array_unshift($expectedLog['error'], $error);
			}
		}

		$this->assertParsing($text, $expectedXml, $expectedLog);
	}

	protected function addRangedBBCode($min = 5, $max = 10)
	{
		$this->cb->BBCodes->addBBCode(
			'X',
			array(
				'attrs' => array(
					'attr' => array(
						'type' => 'range',
						'min'  => $min,
						'max'  => $max
					)
				)
			)
		);
	}

	//==========================================================================
	// Rules
	//==========================================================================

	public function testFulfilledRequireParentRuleAllowsTag()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a][b]stuff[/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st>stuff<et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	public function testFulfilledRequireParentRuleAllowsTagDespitePrefix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a:123][b]stuff[/b][/a:123]',
			'<rt><A><st>[a:123]</st><B><st>[b]</st>stuff<et>[/b]</et></B><et>[/a:123]</et></A></rt>'
		);
	}

	public function testUnfulfilledRequireParentRuleBlocksTag()
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

	public function testUnfulfilledRequireParentRuleBlocksTagDespiteAscendant()
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

	public function testCloseParentRuleIsApplied()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p]one[p]two',
			'<rt><P><st>[p]</st>one</P><P><st>[p]</st>two</P></rt>'
		);
	}

	/**
	* @depends testCloseParentRuleIsApplied
	*/
	public function testCloseParentRuleIsAppliedOnTagWithIdenticalSuffix()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p:123]one[p:123]two',
			'<rt><P><st>[p:123]</st>one</P><P><st>[p:123]</st>two</P></rt>'
		);
	}

	/**
	* @depends testCloseParentRuleIsApplied
	*/
	public function testCloseParentRuleIsAppliedOnTagWithDifferentSuffix()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p:123]one[p:456]two',
			'<rt><P><st>[p:123]</st>one</P><P><st>[p:456]</st>two</P></rt>'
		);
	}

	public function testDenyRuleBlocksTag()
	{
		$this->cb->BBCodes->addBBCode('a', array('defaultRule' => 'allow'));
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('a', 'deny', 'b');

		$this->assertParsing(
			'[a]..[b][/b]..[/a]',
			'<rt><A><st>[a]</st>..[b][/b]..<et>[/a]</et></A></rt>'
		);
	}

	public function testAllowRuleAllowsTag()
	{
		$this->cb->BBCodes->addBBCode('a', array('defaultRule' => 'deny'));
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('a', 'allow', 'b');

		$this->assertParsing(
			'[a][b][/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	public function testRequireAscendantRuleIsFulfilledByParent()
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
	* @depends testRequireAscendantRuleIsFulfilledByParent
	*/
	public function testRequireAscendantRuleIsFulfilledByParentWithSuffix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a:123][b][/b][/a:123]',
			'<rt><A><st>[a:123]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a:123]</et></A></rt>'
		);
	}

	public function testRequireAscendantRuleIsFulfilledByAscendant()
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
	* @depends testRequireAscendantRuleIsFulfilledByAscendant
	*/
	public function testRequireAscendantRuleIsFulfilledByAscendantWithSuffix()
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

	public function testUnfulfilledRequireAscendantRuleBlocksTag()
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

	/**
	* @dataProvider getParamStuff
	*/
	public function estParamStuff()
	{
		$this->cb->BBCodes->addBBCode('b');

		$this->cb->BBCodes->addBBCode('url');
		$this->cb->addTagAttribute('url', 'href', 'url');
		$this->cb->addTagAttribute('url', 'title', 'text', array('isRequired' => false));

		$this->cb->BBCodes->addBBCode('x');
		$this->cb->addTagAttribute('x', 'foo', 'text', array('isRequired' => false));
		$this->cb->addTagAttribute('x', 'range', 'range', array('isRequired' => false, 'min' => 7, 'max' => 77));
		$this->cb->addTagAttribute('x', 'replace', 'regexp', array(
			'isRequired' => false,
			'regexp'  => '/^(FOO)(BAR)$/',
			'replace' => '$2$1'
		));

		$types = array(
			'custom',
			'float',
			'integer', 'int',
			'number', 'uint',
			'id', 'identifier',
			'color',
			'simpletext',
			'undefined'
		);

		foreach ($types as $type)
		{
			$this->cb->addTagAttribute('x', $type, $type, array('isRequired' => false));
		}

		$this->cb->setFilter('custom', function($v) { return $v; });

		// [size] BBCode with custom font-size filter
		$this->cb->BBCodes->addBBCode('size', array('defaultParam' => 'size'));
		$this->cb->addTagAttribute('size', 'size', 'font-size');

		$that = $this;
		$callback = function($v, $conf) use ($that)
		{
			if ($v < $conf['min'])
			{
				$that->parser->log('warning', array(
					'msg'    => 'Font size must be at least %d',
					'params' => array($conf['min'])
				));
				return $conf['min'];
			}
			elseif ($v > $conf['max'])
			{
				$that->parser->log('warning', array(
					'msg'    => 'Font size is limited to %d',
					'params' => array($conf['max'])
				));
				return $conf['max'];
			}
			return $v;
		};
		$this->cb->setFilter('font-size', $callback, array(
			'min' => 7,
			'max' => 20
		));

		$this->cb->disallowHost('EVIL.example.com');
		$this->cb->disallowHost('*.xxx');
		$this->cb->disallowHost('reallyevil.*');
		$this->cb->allowScheme('ftp');

		// Regexp stuff
		$this->cb->BBCodes->addBBCode('align');
		$this->cb->addTagAttribute('align', 'align', 'regexp', array('regexp' => '/^(left|right)$/i'));

		call_user_func_array(array($this, 'assertParsing'), func_get_args());
	}

	public function testUndefinedAttributesDoNotAppearInXml()
	{
		$this->cb->BBCodes->addBBCode('x');
		$this->assertParsing(
			'[x unknown=123 /]',
			'<rt><X>[x unknown=123 /]</X></rt>'
		);
	}

	public function testOverlappingTagsAreSortedOut()
	{
		$this->cb->BBCodes->addBBCode('x',
			array('attrs' => array(
				'foo' => array('type' => 'text')
			))
		);
		$this->assertParsing(
			'[x foo="[b]bar[/b]" /]',
			'<rt><X foo="[b]bar[/b]">[x foo=&quot;[b]bar[/b]&quot; /]</X></rt>'
		);
	}

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
				'msg'    => "URL scheme '%s' is not allowed",
				'params' => array('ftp')
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
				'msg'    => "URL host '%s' is not allowed",
				'params' => array('evil.example.com')
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
				'msg'    => "URL host '%s' is not allowed",
				'params' => array('evil.example.com')
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
				'msg'    => "URL host '%s' is not allowed",
				'params' => array('evil.example.com')
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
				'msg'    => "URL host '%s' is not allowed",
				'params' => array('evil.example.com')
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
		$this->addRangedBBCode();

		$this->assertParsing(
			'[X attr="8"][/X]',
			'<rt><X attr="8"><st>[X attr="8"]</st><et>[/X]</et></X></rt>'
		);
	}

	public function testRangeFilterAllowsNegativeIntegersWithinRange()
	{
		$this->addRangedBBCode(-10, 10);

		$this->assertParsing(
			'[X attr="-8"][/X]',
			'<rt><X attr="-8"><st>[X attr="-8"]</st><et>[/X]</et></X></rt>'
		);
	}

	public function testRangeFilterRejectsDecimalNumbers()
	{
		$this->addRangedBBCode();

		$this->assertParsing(
			'[X attr="3.1"][/X]',
			'<pt>[X attr="3.1"][/X]</pt>',
			array(
				'error' => array(
					array(
						'msg'    => "Invalid attribute '%s'",
						'params' => array('attr')
					)
				)
			)
		);
	}

	public function testRangeFilterAdjustsValuesBelowRange()
	{
		$this->addRangedBBCode();

		$this->assertParsing(
			'[X attr="3"][/X]',
			'<rt><X attr="5"><st>[X attr="3"]</st><et>[/X]</et></X></rt>',
			array(
				'warning' => array(
					array(
						'msg' => 'Attribute \'%1$s\' outside of range, value adjusted up to %2$d',
						'params' => array(5)
					)
				)
			)
		);
	}

	public function testRangeFilterAdjustsValuesAboveRange()
	{
		$this->addRangedBBCode();

		$this->assertParsing(
			'[X attr="30"][/X]',
			'<rt><X attr="10"><st>[X attr="30"]</st><et>[/X]</et></X></rt>',
			array(
				'warning' => array(
					array(
						'msg' => 'Attribute \'%1$s\' outside of range, value adjusted down to %2$d',
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

	public function testUndefinedFilterRejectsEverything()
	{
		$this->assertAttributeIsInvalid('whoknows', 'foobar');
	}
}