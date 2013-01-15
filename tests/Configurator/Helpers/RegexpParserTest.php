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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => 'foo',
				'tokens'    => array()
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => 'iD',
				'regexp'    => 'foo',
				'tokens'    => array()
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z]',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 5,
						'type' => 'characterClass',
						'content' => 'a-z',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z]+',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 6,
						'type' => 'characterClass',
						'content' => 'a-z',
						'quantifiers' => '+'
					)
				)
			),
			RegexpParser::parse(
				'#[a-z]+#'
			)
		);
	}

	/**
	* @testdox parse() parses character classes that end with an escaped ]
	*/
	public function testCanParseRegexps5()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z\\]]',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 7,
						'type' => 'characterClass',
						'content' => 'a-z\\]',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '\\[x\\]',
				'tokens'    => array()
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '\\(x\\)',
				'tokens'    => array()
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?:x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'options' => '',
						'content' => 'x+',
						'endToken' => 1
					),
					array(
						'pos' => 5,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?>x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'subtype' => 'atomic',
						'content' => 'x+',
						'endToken' => 1
					),
					array(
						'pos' => 5,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			RegexpParser::parse(
				'#(?>x+)#'
			)
		);
	}

	/**
	* @testdox parse() parses non-capturing subpatterns with quantifiers
	*/
	public function testCanParseRegexps9()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?:x+)++',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'options' => '',
						'content' => 'x+',
						'endToken' => 1
					),
					array(
						'pos' => 5,
						'len' => 3,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => '++'
					)
				)
			),
			RegexpParser::parse(
				'#(?:x+)++#'
			)
		);
	}

	/**
	* @testdox parse() parses non-capturing subpatterns with options
	*/
	public function testCanParseRegexps10()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?i:x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'nonCapturingSubpatternStart',
						'options' => 'i',
						'content' => 'x+',
						'endToken' => 1
					),
					array(
						'pos' => 6,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?i)abc',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'option',
						'options' => 'i'
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<foo>x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 7,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'content' => 'x+',
						'endToken' => 1
					),
					array(
						'pos' => 9,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?P<foo>x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 8,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'content' => 'x+',
						'endToken' => 1
					),
					array(
						'pos' => 10,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => "(?'foo'x+)",
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 7,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'content' => 'x+',
						'endToken' => 1
					),
					array(
						'pos' => 9,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '/',
				'modifiers' => '',
				'regexp'    => '(x+)(abc\\d+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 1,
						'type' => 'capturingSubpatternStart',
						'content' => 'x+',
						'endToken' => 1
					),
					array(
						'pos' => 3,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					),
					array(
						'pos' => 4,
						'len' => 1,
						'type' => 'capturingSubpatternStart',
						'content' => 'abc\\d+',
						'endToken' => 3
					),
					array(
						'pos' => 11,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?=foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'lookaheadAssertionStart',
						'content' => 'foo',
						'endToken' => 1
					),
					array(
						'pos' => 6,
						'len' => 1,
						'type' => 'lookaheadAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?!foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'negativeLookaheadAssertionStart',
						'content' => 'foo',
						'endToken' => 1
					),
					array(
						'pos' => 6,
						'len' => 1,
						'type' => 'negativeLookaheadAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<=foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'lookbehindAssertionStart',
						'content' => 'foo',
						'endToken' => 1
					),
					array(
						'pos' => 7,
						'len' => 1,
						'type' => 'lookbehindAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
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
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<!foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'negativeLookbehindAssertionStart',
						'content' => 'foo',
						'endToken' => 1
					),
					array(
						'pos' => 7,
						'len' => 1,
						'type' => 'negativeLookbehindAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
			RegexpParser::parse(
				'#(?<!foo)#'
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
				$msg = var_export($regexp, true) . ' should match ' . json_encode($char);
			}
			else
			{
				$methodName = 'assertNotRegExp';
				$msg = var_export($regexp, true) . ' should not match ' . json_encode($char);
			}

			$this->$methodName($allowedCharRegexp, (string) $char, $msg);
		}
	}

	public function getAllowedCharacterRegexpData()
	{
		return array(
			array(
				'/^a+$/',
				array(
					'a'  => true,
					'A'  => false,
					'b'  => false,
					'+'  => false,
					"\n" => true
				)
			),
			array(
				'/^a+$/Di',
				array(
					'a'  => true,
					'A'  => true,
					'b'  => false
				)
			),
			array(
				'/^a+$/D',
				array(
					'a'  => true,
					'b'  => false,
					'+'  => false,
					"\n" => false
				)
			),
			array(
				'/a/D',
				array(
					'a'  => true,
					'b'  => true,
					"\n" => true
				)
			),
			array(
				'/^a$/Dm',
				array(
					'a'  => true,
					'b'  => true,
					"\n" => true
				)
			),
			array(
				'/^\\w+$/D',
				array(
					'a'  => true,
					'b'  => true,
					'+'  => false,
					'\\' => false
				)
			),
			array(
				'/^[0-4][6-9]$/D',
				array(
					'0' => true,
					'2' => true,
					'4' => true,
					'5' => false,
					'8' => true,
					'[' => false,
					']' => false,
					'-' => false
				)
			),
			array(
				'/^[-]$/D',
				array(
					'-' => true,
					'[' => false,
					']' => false
				)
			),
			array(
				'/^[a][-][z]$/D',
				array(
					'-' => true,
					'[' => false,
					']' => false,
					'a' => true,
					'x' => false,
					'z' => true
				)
			),
			array(
				'/^[^a-z]$/D',
				array(
					'-' => true,
					'[' => true,
					'a' => false,
					'x' => false,
					'z' => false,
					'A' => true
				)
			),
			array(
				'/^[^a-z]$/Di',
				array(
					'a' => false,
					'A' => false
				)
			),
			array(
				'/^a|a$/D',
				array(
					'a' => true,
					'b' => true
				)
			),
			array(
				'/^.$/D',
				array(
					'a'  => true,
					"\n" => false
				)
			),
			array(
				'/^.$/',
				array(
					'a'  => true,
					"\n" => true
				)
			),
			array(
				'/^.$/Ds',
				array(
					'a'  => true,
					"\n" => true
				)
			),
			array(
				'/^.$/',
				array(
					'é' => true
				)
			),
			array(
				'/^Pokémon$/iu',
				array(
					'é' => true,
					'É' => true
				)
			),
			array(
				'/^\\pL$/iu',
				array(
					'é' => true,
					'É' => true
				)
			),
			array(
				'/^(left|right|center)$/',
				array(
					'l' => true,
					'(' => false,
					'|' => false,
					')' => false
				)
			),
			array(
				'/^[$]$/',
				array(
					'$' => true,
					'[' => false
				)
			),
			array(
				'/^\\$$/',
				array(
					'$'  => true,
					'\\' => false
				)
			),
			array(
				'/^\\\\\\$$/',
				array(
					'$'  => true,
					'\\' => true
				)
			),
			array(
				'/^[\\^]$/',
				array(
					'^'  => true,
					'\\' => false
				)
			),
			array(
				'/^[\\\\^]$/',
				array(
					'^'  => true,
					'\\' => true
				)
			),
			array(
				'/^\\^$/',
				array(
					'^'  => true,
					'\\' => false
				)
			),
			array(
				'/^\\\\\\^$/',
				array(
					'^'  => true,
					'\\' => true
				)
			),
			array(
				'/^\\($/',
				array(
					'('  => true,
					'\\' => false
				)
			),
			array(
				'/^\\\\\\($/',
				array(
					'('  => true,
					'\\' => true
				)
			),
			array(
				'/^(a)\\1$/',
				array(
					'a'  => true,
					'\\' => false,
					'1'  => false
				)
			),
			array(
				'/^(1)\\1$/',
				array(
					'1'  => true,
					'\\' => false
				)
			),
			array(
				'/^\\050$/',
				array(
					'0' => false,
					'5' => false,
					'(' => true
				)
			),
			array(
				'/^[a-z\\050]$/',
				array(
					'0' => false,
					'5' => false,
					'(' => true
				)
			),
			array(
				'/^a\\b\\B\\A\\Z\\z\\Gc$/',
				array(
					'a' => true,
					'b' => false,
					'B' => false,
					'A' => false,
					'Z' => false,
					'z' => false,
					'G' => false,
					'c' => true
				)
			),
		);
	}
}