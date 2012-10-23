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
	* @testdox pcreToJs() can convert plain regexps
	*/
	public function testConvertRegexp1()
	{
		$this->assertEquals(
			'/foo/',
			RegexpParser::pcreToJs('#foo#')
		);
	}

	/**
	* @testdox pcreToJs() escapes forward slashes
	*/
	public function testConvertRegexpEscape()
	{
		$this->assertEquals(
			'/fo\\/o/',
			RegexpParser::pcreToJs('#fo/o#')
		);
	}

	/**
	* @testdox pcreToJs() does not double-escape forward slashes that are already escaped
	*/
	public function testConvertRegexpNoDoubleEscape()
	{
		$this->assertEquals(
			'/fo\\/o/',
			RegexpParser::pcreToJs('#fo\\/o#')
		);
	}

	/**
	* @testdox pcreToJs() does not "eat" backslashes while escaping forward slashes
	*/
	public function testConvertRegexpDoesNotEatEscapedBackslashes()
	{
		$this->assertEquals(
			'/fo\\\\\\/o/',
			RegexpParser::pcreToJs('#fo\\\\/o#')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with the "i" modifier
	*/
	public function testConvertRegexp2()
	{
		$this->assertEquals(
			'/foo/i',
			RegexpParser::pcreToJs('#foo#i')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with capturing subpatterns
	*/
	public function testConvertRegexp3()
	{
		$this->assertEquals(
			'/f(o)o/',
			RegexpParser::pcreToJs('#f(o)o#')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with non-capturing subpatterns
	*/
	public function testConvertRegexp4()
	{
		$this->assertEquals(
			'/f(?:o)o/',
			RegexpParser::pcreToJs('#f(?:o)o#')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with non-capturing subpatterns with a quantifier
	*/
	public function testConvertRegexp5()
	{
		$this->assertEquals(
			'/f(?:oo)+/',
			RegexpParser::pcreToJs('#f(?:oo)+#')
		);
	}

	/**
	* @testdox pcreToJs() converts greedy quantifiers to normal quantifiers in non-capturing subpatterns
	*/
	public function testConvertRegexp5b()
	{
		$this->assertEquals(
			'/f(?:o)+(?:o)*/',
			RegexpParser::pcreToJs('#f(?:o)++(?:o)*+#')
		);
	}

	/**
	* @testdox pcreToJs() throws a RuntimeException on options (?i)
	* @expectedException RuntimeException
	* @expectedExceptionMessage Regexp options are not supported
	*/
	public function testConvertRegexpException1()
	{
		RegexpParser::pcreToJs('#(?i)x#');
	}

	/**
	* @testdox pcreToJs() throws a RuntimeException on subpattern options (?i:)
	* @expectedException RuntimeException
	* @expectedExceptionMessage Subpattern options are not supported
	*/
	public function testConvertRegexpException2()
	{
		RegexpParser::pcreToJs('#(?i:x)#');
	}

	/**
	* @testdox pcreToJs() can convert regexps with character classes with a quantifier
	*/
	public function testConvertRegexp6()
	{
		$this->assertEquals(
			'/[a-z]+/',
			RegexpParser::pcreToJs('#[a-z]+#')
		);
	}

	/**
	* @testdox pcreToJs() converts greedy quantifiers to normal quantifiers in character classes
	*/
	public function testConvertRegexp6b()
	{
		$this->assertEquals(
			'/[a-z]+[a-z]*/',
			RegexpParser::pcreToJs('/[a-z]++[a-z]*+/')
		);
	}

	/**
	* @testdox pcreToJs() replaces \pL with the full character range in character classes
	*/
	public function testConvertRegexp7()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[0-9' . $unicodeRange . '\\]/$#D',
			RegexpParser::pcreToJs('#[0-9\\pL]#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \p{L} with the full character range in character classes
	*/
	public function testConvertRegexp7b()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[0-9' . $unicodeRange . '\\]/$#D',
			RegexpParser::pcreToJs('#[0-9\\p{L}]#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \pL outside of character classes with a character class containing the full character range
	*/
	public function testConvertRegexp8()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]00\\[' . $unicodeRange . '\\]/$#D',
			RegexpParser::pcreToJs('#\\pL00\\pL#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \p{L} outside of character classes with a character class containing the full character range
	*/
	public function testConvertRegexp8b()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]00\\[' . $unicodeRange . '\\]/$#D',
			RegexpParser::pcreToJs('#\\p{L}00\\p{L}#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \p{^L} with a character class containing the full character range
	*/
	public function testConvertRegexp8c()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';

		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]/$#D',
			RegexpParser::pcreToJs('#\\p{^L}#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \p{^L} with a character class equivalent to \PL
	*/
	public function testConvertRegexp8d()
	{
		$this->assertSame(
			RegexpParser::pcreToJs('#\\PL#'),
			RegexpParser::pcreToJs('#\\p{^L}#')
		);
	}

	/**
	* @testdox pcreToJs() replaces \P{^L} with a character class equivalent to \pL
	*/
	public function testConvertRegexp8e()
	{
		$this->assertSame(
			RegexpParser::pcreToJs('#\\pL#'),
			RegexpParser::pcreToJs('#\\P{^L}#')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with lookahead assertions
	*/
	public function testConvertRegexpLookahead()
	{
		$this->assertEquals(
			'/(?=foo)|(?=bar)/',
			RegexpParser::pcreToJs('#(?=foo)|(?=bar)#')
		);
	}

	/**
	* @testdox pcreToJs() can convert regexps with negative lookahead assertions
	*/
	public function testConvertRegexpNegativeLookahead()
	{
		$this->assertEquals(
			'/(?!foo)|(?!bar)/',
			RegexpParser::pcreToJs('#(?!foo)|(?!bar)#')
		);
	}

	/**
	* @testdox pcreToJs() throws a RuntimeException on lookbehind assertions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Lookbehind assertions are not supported
	*/
	public function testConvertRegexpExceptionOnLookbehind()
	{
		RegexpParser::pcreToJs('#(?<=foo)x#');
	}

	/**
	* @testdox pcreToJs() throws a RuntimeException on negative lookbehind assertions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Negative lookbehind assertions are not supported
	*/
	public function testConvertRegexpExceptionOnNegativeLookbehind()
	{
		RegexpParser::pcreToJs('#(?<!foo)x#');
	}

	/**
	* @testdox pcreToJs() converts . to [\s\S] outside of character classes if the "s" modifier is set
	*/
	public function testConvertRegexpDotAll()
	{
		$this->assertEquals(
			'/foo([\\s\\S]*)bar/',
			RegexpParser::pcreToJs('#foo(.*)bar#s')
		);
	}

	/**
	* @testdox pcreToJs() does not convert . to [\s\S] if the "s" modifier is not set
	*/
	public function testConvertRegexpDotWithoutDotAll()
	{
		$this->assertEquals(
			'/foo(.*)bar/',
			RegexpParser::pcreToJs('#foo(.*)bar#')
		);
	}

	/**
	* @testdox pcreToJs() does not convert . inside of character classes
	*/
	public function testConvertRegexpDotInCharacterClasses()
	{
		$this->assertEquals(
			'/foo[.]+bar/',
			RegexpParser::pcreToJs('#foo[.]+bar#s')
		);
	}

	/**
	* @testdox pcreToJs() converts named captures into normal captures
	*/
	public function testConvertRegexpNamedCaptures()
	{
		$this->assertEquals(
			'/x([0-9]+)([a-z]+)x/',
			RegexpParser::pcreToJs('#x(?<foo>[0-9]+)(?<bar>[a-z]+)x#', $map)
		);
	}

	/**
	* @testdox pcreToJs() replaces its second parameter with an array that maps named captures to their index
	*/
	public function testConvertRegexpNamedCapturesMap()
	{
		$map = null;

		$this->assertEquals(
			'/x([0-9]+)([a-z]+)x/',
			RegexpParser::pcreToJs('#x(?<foo>[0-9]+)(?<bar>[a-z]+)x#', $map)
		);

		$this->assertEquals(
			array('foo' => 1, 'bar' => 2),
			$map
		);
	}

	/**
	* NOTE: this is a regression test
	* @testdox pcreToJs() correctly converts /(?:foo)(?<z>bar)/ to /(?:foo)(bar)/
	*/
	public function testConvertNamedSubatternAfterNormalSubpattern()
	{
		$map = null;

		$this->assertSame(
			'/(?:foo)(bar)/',
			RegexpParser::pcreToJs('/(?:foo)(?<z>bar)/', $map)
		);
	}
}