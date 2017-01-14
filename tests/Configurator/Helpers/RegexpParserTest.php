<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\RegexpParser
*/
class RegexpParserTest extends Test
{
	/**
	* @testdox parse() can parse plain regexps
	*/
	public function testCanParseRegexps1()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => 'foo',
				'tokens'    => []
			],
			RegexpParser::parse(
				'#foo#'
			)
		);
	}

	/**
	* @testdox parse() throws a RuntimeException if delimiters can't be parsed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not parse regexp delimiters
	*/
	public function testInvalidRegexpsException1()
	{
		RegexpParser::parse('#foo/iD');
	}

	/**
	* @testdox parse() parses pattern modifiers
	*/
	public function testCanParseRegexps2()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => 'iD',
				'regexp'    => 'foo',
				'tokens'    => []
			],
			RegexpParser::parse(
				'#foo#iD'
			)
		);
	}

	/**
	* @testdox parse() parses character classes
	*/
	public function testCanParseRegexps3()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z]',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 5,
						'type' => 'characterClass',
						'content' => 'a-z',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#[a-z]#'
			)
		);
	}

	/**
	* @testdox parse() parses character classes with quantifiers
	*/
	public function testCanParseRegexps4()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z]+',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 6,
						'type' => 'characterClass',
						'content' => 'a-z',
						'quantifiers' => '+'
					]
				]
			],
			RegexpParser::parse(
				'#[a-z]+#'
			)
		);
	}

	/**
	* @testdox parse() parses character classes with quantifiers and greediness operator
	*/
	public function testCanParseRegexps4b()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z]+?',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 7,
						'type' => 'characterClass',
						'content' => 'a-z',
						'quantifiers' => '+?'
					]
				]
			],
			RegexpParser::parse(
				'#[a-z]+?#'
			)
		);
	}

	/**
	* @testdox parse() parses character classes that end with an escaped ]
	*/
	public function testCanParseRegexps5()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z\\]]',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 7,
						'type' => 'characterClass',
						'content' => 'a-z\\]',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#[a-z\\]]#'
			)
		);
	}

	/**
	* @testdox parse() throws a RuntimeException if a character class is not properly closed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find matching bracket from pos 0
	*/
	public function testInvalidRegexpsException2()
	{
		RegexpParser::parse('#[a-z)#');
	}

	/**
	* @testdox parse() correctly parses escaped brackets
	*/
	public function testCanParseRegexps6()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '\\[x\\]',
				'tokens'    => []
			],
			RegexpParser::parse(
				'#\\[x\\]#'
			)
		);
	}

	/**
	* @testdox parse() correctly parses escaped parentheses
	*/
	public function testCanParseRegexps7()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '\\(x\\)',
				'tokens'    => []
			],
			RegexpParser::parse(
				'#\\(x\\)#'
			)
		);
	}

	/**
	* @testdox parse() parses non-capturing subpatterns
	*/
	public function testCanParseRegexps8()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?:x+)',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'options' => '',
						'content' => 'x+',
						'endToken' => 1
					],
					[
						'pos' => 5,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#(?:x+)#'
			)
		);
	}

	/**
	* @testdox parse() parses non-capturing subpatterns with atomic grouping
	*/
	public function testCanParseRegexps8b()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?>x+)',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'subtype' => 'atomic',
						'content' => 'x+',
						'endToken' => 1
					],
					[
						'pos' => 5,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#(?>x+)#'
			)
		);
	}

	/**
	* @testdox parse() parses non-capturing subpatterns with (possessive) quantifier
	*/
	public function testCanParseRegexps9()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?:x+)++',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'options' => '',
						'content' => 'x+',
						'endToken' => 1
					],
					[
						'pos' => 5,
						'len' => 3,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => '++'
					]
				]
			],
			RegexpParser::parse(
				'#(?:x+)++#'
			)
		);
	}

	/**
	* @testdox parse() parses non-capturing subpatterns with (ungreedy) quantifier
	*/
	public function testCanParseRegexps9b()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?:x+)+?',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'options' => '',
						'content' => 'x+',
						'endToken' => 1
					],
					[
						'pos' => 5,
						'len' => 3,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => '+?'
					]
				]
			],
			RegexpParser::parse(
				'#(?:x+)+?#'
			)
		);
	}

	/**
	* @testdox parse() parses non-capturing subpatterns with options
	*/
	public function testCanParseRegexps10()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?i:x+)',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 4,
						'type' => 'nonCapturingSubpatternStart',
						'options' => 'i',
						'content' => 'x+',
						'endToken' => 1
					],
					[
						'pos' => 6,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#(?i:x+)#'
			)
		);
	}

	/**
	* @testdox parse() parses option settings
	*/
	public function testCanParseRegexps11()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?i)abc',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 4,
						'type' => 'option',
						'options' => 'i'
					]
				]
			],
			RegexpParser::parse(
				'#(?i)abc#'
			)
		);
	}

	/**
	* @testdox parse() parses named subpatterns using the (?<name>) syntax
	*/
	public function testCanParseRegexps12()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<foo>x+)',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 7,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'content' => 'x+',
						'endToken' => 1
					],
					[
						'pos' => 9,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#(?<foo>x+)#'
			)
		);
	}

	/**
	* @testdox parse() parses named subpatterns using the (?P<name>) syntax
	*/
	public function testCanParseRegexps13()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?P<foo>x+)',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 8,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'content' => 'x+',
						'endToken' => 1
					],
					[
						'pos' => 10,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#(?P<foo>x+)#'
			)
		);
	}

	/**
	* @testdox parse() parses named subpatterns using the (?'name') syntax
	*/
	public function testCanParseRegexps14()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => "(?'foo'x+)",
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 7,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'content' => 'x+',
						'endToken' => 1
					],
					[
						'pos' => 9,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				"#(?'foo'x+)#"
			)
		);
	}

	/**
	* @testdox parse() parses capturing subpatterns
	*/
	public function testCanParseRegexps15()
	{
		$this->assertEquals(
			[
				'delimiter' => '/',
				'modifiers' => '',
				'regexp'    => '(x+)(abc\\d+)',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 1,
						'type' => 'capturingSubpatternStart',
						'content' => 'x+',
						'endToken' => 1
					],
					[
						'pos' => 3,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					],
					[
						'pos' => 4,
						'len' => 1,
						'type' => 'capturingSubpatternStart',
						'content' => 'abc\\d+',
						'endToken' => 3
					],
					[
						'pos' => 11,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'/(x+)(abc\\d+)/'
			)
		);
	}

	/**
	* @testdox parse() throws a RuntimeException if an unmatched right parenthesis is found
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find matching pattern start for right parenthesis at pos 3
	*/
	public function testInvalidRegexpsException4()
	{
		RegexpParser::parse('#a-z)#');
	}

	/**
	* @testdox parse() throws a RuntimeException if an unmatched left parenthesis is found
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find matching pattern end for left parenthesis at pos 0
	*/
	public function testInvalidRegexpsException5()
	{
		RegexpParser::parse('#(a-z#');
	}

	/**
	* @testdox parse() throws a RuntimeException on unsupported subpatterns
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unsupported subpattern type at pos 0
	*/
	public function testInvalidRegexpsUnsupportedSubpatternException()
	{
		RegexpParser::parse('#(?(condition)yes-pattern|no-pattern)#');
	}

	/**
	* @testdox parse() parses lookahead assertions
	*/
	public function testCanParseRegexps16()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?=foo)',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 3,
						'type' => 'lookaheadAssertionStart',
						'content' => 'foo',
						'endToken' => 1
					],
					[
						'pos' => 6,
						'len' => 1,
						'type' => 'lookaheadAssertionEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#(?=foo)#'
			)
		);
	}

	/**
	* @testdox parse() parses negative lookahead assertions
	*/
	public function testCanParseRegexps17()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?!foo)',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 3,
						'type' => 'negativeLookaheadAssertionStart',
						'content' => 'foo',
						'endToken' => 1
					],
					[
						'pos' => 6,
						'len' => 1,
						'type' => 'negativeLookaheadAssertionEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#(?!foo)#'
			)
		);
	}

	/**
	* @testdox parse() parses lookbehind assertions
	*/
	public function testCanParseRegexps18()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<=foo)',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 4,
						'type' => 'lookbehindAssertionStart',
						'content' => 'foo',
						'endToken' => 1
					],
					[
						'pos' => 7,
						'len' => 1,
						'type' => 'lookbehindAssertionEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#(?<=foo)#'
			)
		);
	}

	/**
	* @testdox parse() parses negative lookbehind assertions
	*/
	public function testCanParseRegexps19()
	{
		$this->assertEquals(
			[
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<!foo)',
				'tokens'    => [
					[
						'pos' => 0,
						'len' => 4,
						'type' => 'negativeLookbehindAssertionStart',
						'content' => 'foo',
						'endToken' => 1
					],
					[
						'pos' => 7,
						'len' => 1,
						'type' => 'negativeLookbehindAssertionEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'#(?<!foo)#'
			)
		);
	}

	/**
	* @testdox parse() parses escaped right parentheses
	*/
	public function testCanParseRegexps20()
	{
		$this->assertEquals(
			[
				'delimiter' => '/',
				'modifiers' => '',
				'regexp'    => '(\\(?)',
				'tokens'    => [
					[
						'pos'      => 0,
						'len'      => 1,
						'type'     => 'capturingSubpatternStart',
						'content'  => '\\(?',
						'endToken' => 1
					],
					[
						'pos'         => 4,
						'len'         => 1,
						'type'        => 'capturingSubpatternEnd',
						'quantifiers' => ''
					]
				]
			],
			RegexpParser::parse(
				'/(\(?)/'
			)
		);
	}

	/**
	* @testdox getAllowedCharacterRegexp() works
	* @dataProvider getAllowedCharacterRegexpData
	*/
	public function testGetAllowedCharacterRegexp($regexp, $results)
	{
		$allowedCharRegexp = RegexpParser::getAllowedCharacterRegexp($regexp);

		foreach ($results as $char => $result)
		{
			if ($result)
			{
				$methodName = 'assertRegExp';
				$msg = var_export($regexp, true) . ' should match ' . var_export($char, true);
			}
			else
			{
				$methodName = 'assertNotRegExp';
				$msg = var_export($regexp, true) . ' should not match ' . var_export($char, true);
			}

			$this->$methodName($allowedCharRegexp, (string) $char, $msg);
		}
	}

	public function getAllowedCharacterRegexpData()
	{
		return [
			[
				'/^a+$/',
				[
					'a'  => true,
					'A'  => false,
					'b'  => false,
					'+'  => false,
					"\n" => true
				]
			],
			[
				'/^a+$/Di',
				[
					'a'  => true,
					'A'  => true,
					'b'  => false
				]
			],
			[
				'/^a+$/D',
				[
					'a'  => true,
					'b'  => false,
					'+'  => false,
					"\n" => false
				]
			],
			[
				'/a/D',
				[
					'a'  => true,
					'b'  => true,
					"\n" => true
				]
			],
			[
				'/^a$/Dm',
				[
					'a'  => true,
					'b'  => true,
					"\n" => true
				]
			],
			[
				'/^\\w+$/D',
				[
					'a'  => true,
					'b'  => true,
					'+'  => false,
					'\\' => false
				]
			],
			[
				'/^[0-4][6-9]$/D',
				[
					'0' => true,
					'2' => true,
					'4' => true,
					'5' => false,
					'8' => true,
					'[' => false,
					']' => false,
					'-' => false
				]
			],
			[
				'/^[-]$/D',
				[
					'-' => true,
					'[' => false,
					']' => false
				]
			],
			[
				'/^[a][-][z]$/D',
				[
					'-' => true,
					'[' => false,
					']' => false,
					'a' => true,
					'x' => false,
					'z' => true
				]
			],
			[
				'/^[^a-z]$/D',
				[
					'-' => true,
					'[' => true,
					'a' => false,
					'x' => false,
					'z' => false,
					'A' => true
				]
			],
			[
				'/^[^a-z]$/Di',
				[
					'a' => false,
					'A' => false
				]
			],
			[
				'/^a|a$/D',
				[
					'a' => true,
					'b' => true
				]
			],
			[
				'/^.$/D',
				[
					'a'  => true,
					"\n" => false
				]
			],
			[
				'/^.$/',
				[
					'a'  => true,
					"\n" => true
				]
			],
			[
				'/^.$/Ds',
				[
					'a'  => true,
					"\n" => true
				]
			],
			[
				'/^.$/',
				[
					'é' => true
				]
			],
			[
				'/^Pokémon$/iu',
				[
					'é' => true,
					'É' => true
				]
			],
			[
				'/^\\pL$/iu',
				[
					'é' => true,
					'É' => true
				]
			],
			[
				'/^(left|right|center)$/',
				[
					'l' => true,
					'n' => true,
					'z' => false,
					'^' => false,
					'$' => false,
					'(' => false,
					'|' => false,
					')' => false
				]
			],
			[
				'/^((?:left|right)|(?:center))$/',
				[
					'l' => true,
					'n' => true,
					'z' => false,
					'^' => false,
					'$' => false,
					'(' => false,
					'|' => false,
					')' => false
				]
			],
			[
				'/^(?:left|right)$|^(?:center)$/',
				[
					'l' => true,
					'n' => true,
					'z' => false,
					'^' => false,
					'$' => false,
					'(' => false,
					'|' => false,
					')' => false
				]
			],
			[
				'/^(?:left|right)$|(?:center)$/',
				[
					'l' => true,
					'(' => true,
					'|' => true,
					')' => true
				]
			],
			[
				'/^(?:left|right)|^(?:center)$/',
				[
					'l' => true,
					'(' => true,
					'|' => true,
					')' => true
				]
			],
			[
				'/^[$]$/',
				[
					'$' => true,
					'[' => false
				]
			],
			[
				'/^\\$$/',
				[
					'$'  => true,
					'\\' => false
				]
			],
			[
				'/^\\\\\\$$/',
				[
					'$'  => true,
					'\\' => true
				]
			],
			[
				'/^[\\^]$/',
				[
					'^'  => true,
					'\\' => false
				]
			],
			[
				'/^[\\\\^]$/',
				[
					'^'  => true,
					'\\' => true
				]
			],
			[
				'/^\\^$/',
				[
					'^'  => true,
					'\\' => false
				]
			],
			[
				'/^\\\\\\^$/',
				[
					'^'  => true,
					'\\' => true
				]
			],
			[
				'/^\\($/',
				[
					'('  => true,
					'\\' => false
				]
			],
			[
				'/^\\\\\\($/',
				[
					'('  => true,
					'\\' => true
				]
			],
			[
				'/^(a)\\1$/',
				[
					'a'  => true,
					'\\' => false,
					'1'  => false
				]
			],
			[
				'/^(1)\\1$/',
				[
					'1'  => true,
					'\\' => false
				]
			],
			[
				'/^\\050$/',
				[
					'0' => false,
					'5' => false,
					'(' => true
				]
			],
			[
				'/^[a-z\\050]$/',
				[
					'0' => false,
					'5' => false,
					'(' => true
				]
			],
			[
				'/^a\\b\\B\\A\\Z\\z\\Gc$/',
				[
					'a' => true,
					'b' => false,
					'B' => false,
					'A' => false,
					'Z' => false,
					'z' => false,
					'G' => false,
					'c' => true
				]
			],
			[
				'#^/$#D',
				[
					'#' => false,
					'/' => true
				]
			],
			[
				'/^a-c$/D',
				[
					'a' => true,
					'b' => false,
					'c' => true,
					'-' => true
				]
			],
			[
				'/^$/D',
				[
					'^' => false,
					'$' => false,
					'x' => false
				]
			],
			[
				'/^(?J)x$/D',
				[
					'J' => false,
					'$' => false,
					'x' => true
				]
			],
			[
				'/^(?!J)x$/D',
				[
					'J' => false,
					'$' => false,
					'x' => true
				]
			],
			[
				'/^(?!J)[xy]$/D',
				[
					'J' => false,
					'x' => true,
					'y' => true
				]
			],
			[
				'/^(?![xy])J$/D',
				[
					'J' => true,
					'x' => false,
					'y' => false
				]
			],
		];
	}

	/**
	* @testdox getCaptureNames() works
	* @dataProvider getGetCaptureNamesTests
	*/
	public function testGetCaptureNamesTests($regexp, array $expected)
	{
		$this->assertSame($expected, RegexpParser::getCaptureNames($regexp));
	}

	public function getGetCaptureNamesTests()
	{
		return [
			[
				'//',
				['']
			],
			[
				'/(.)/',
				['', '']
			],
			[
				'/(x)(?<foo>y)(?<bar>y)/',
				['', '', 'foo', 'bar']
			],
		];
	}
}