<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;

include_once __DIR__ . '/../../bootstrap.php';

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor
*/
class RegexpConvertorTest extends Test
{
	/**
	* @testdox toJS() returns an instance of s9e\TextFormatter\Configurator\JavaScript\RegExp
	*/
	public function testReturnInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\RegExp',
			RegexpConvertor::toJS('//')
		);
	}

	/**
	* @testdox toJS() can convert plain regexps
	*/
	public function testConvertRegexp1()
	{
		$this->assertEquals(
			'/foo/',
			RegexpConvertor::toJS('#foo#')
		);
	}

	/**
	* @testdox toJS() escapes forward slashes
	*/
	public function testConvertRegexpEscape()
	{
		$this->assertEquals(
			'/fo\\/o/',
			RegexpConvertor::toJS('#fo/o#')
		);
	}

	/**
	* @testdox toJS() does not double-escape forward slashes that are already escaped
	*/
	public function testConvertRegexpNoDoubleEscape()
	{
		$this->assertEquals(
			'/fo\\/o/',
			RegexpConvertor::toJS('#fo\\/o#')
		);
	}

	/**
	* @testdox toJS() does not "eat" backslashes while escaping forward slashes
	*/
	public function testConvertRegexpDoesNotEatEscapedBackslashes()
	{
		$this->assertEquals(
			'/fo\\\\\\/o/',
			RegexpConvertor::toJS('#fo\\\\/o#')
		);
	}

	/**
	* @testdox toJS() can convert regexps with the "i" modifier
	*/
	public function testConvertRegexp2()
	{
		$this->assertEquals(
			'/foo/i',
			RegexpConvertor::toJS('#foo#i')
		);
	}

	/**
	* @testdox toJS() can convert regexps with capturing subpatterns
	*/
	public function testConvertRegexp3()
	{
		$this->assertEquals(
			'/f(o)o/',
			RegexpConvertor::toJS('#f(o)o#')
		);
	}

	/**
	* @testdox toJS() can convert regexps with non-capturing subpatterns
	*/
	public function testConvertRegexp4()
	{
		$this->assertEquals(
			'/f(?:o)o/',
			RegexpConvertor::toJS('#f(?:o)o#')
		);
	}

	/**
	* @testdox toJS() can convert regexps with non-capturing subpatterns with a quantifier
	*/
	public function testConvertRegexp5()
	{
		$this->assertEquals(
			'/f(?:oo)+/',
			RegexpConvertor::toJS('#f(?:oo)+#')
		);
	}

	/**
	* @testdox toJS() converts greedy quantifiers to normal quantifiers in non-capturing subpatterns
	*/
	public function testConvertRegexp5b()
	{
		$this->assertEquals(
			'/f(?:o)+(?:o)*/',
			RegexpConvertor::toJS('#f(?:o)++(?:o)*+#')
		);
	}

	/**
	* @testdox toJS() throws a RuntimeException on options (?i)
	* @expectedException RuntimeException
	* @expectedExceptionMessage Regexp options are not supported
	*/
	public function testConvertRegexpException1()
	{
		RegexpConvertor::toJS('#(?i)x#');
	}

	/**
	* @testdox toJS() throws a RuntimeException on subpattern options (?i:)
	* @expectedException RuntimeException
	* @expectedExceptionMessage Subpattern options are not supported
	*/
	public function testConvertRegexpException2()
	{
		RegexpConvertor::toJS('#(?i:x)#');
	}

	/**
	* @testdox toJS() can convert regexps with character classes with a quantifier
	*/
	public function testConvertRegexp6()
	{
		$this->assertEquals(
			'/[a-z]+/',
			RegexpConvertor::toJS('#[a-z]+#')
		);
	}

	/**
	* @testdox toJS() converts greedy quantifiers to normal quantifiers in character classes
	*/
	public function testConvertRegexp6b()
	{
		$this->assertEquals(
			'/[a-z]+[a-z]*/',
			RegexpConvertor::toJS('/[a-z]++[a-z]*+/')
		);
	}

	/**
	* @testdox toJS() replaces \pL with the full character range in character classes
	*/
	public function testConvertRegexp7()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegExp(
			'#^/\\[0-9' . $unicodeRange . '\\]/$#D',
			(string) RegexpConvertor::toJS('#[0-9\\pL]#')
		);
	}

	/**
	* @testdox toJS() replaces \p{L} with the full character range in character classes
	*/
	public function testConvertRegexp7b()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegExp(
			'#^/\\[0-9' . $unicodeRange . '\\]/$#D',
			(string) RegexpConvertor::toJS('#[0-9\\p{L}]#')
		);
	}

	/**
	* @testdox toJS() replaces \pL outside of character classes with a character class containing the full character range
	*/
	public function testConvertRegexp8()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegExp(
			'#^/\\[' . $unicodeRange . '\\]00\\[' . $unicodeRange . '\\]/$#D',
			(string) RegexpConvertor::toJS('#\\pL00\\pL#')
		);
	}

	/**
	* @testdox toJS() replaces \p{L} outside of character classes with a character class containing the full character range
	*/
	public function testConvertRegexp8b()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegExp(
			'#^/\\[' . $unicodeRange . '\\]00\\[' . $unicodeRange . '\\]/$#D',
			(string) RegexpConvertor::toJS('#\\p{L}00\\p{L}#')
		);
	}

	/**
	* @testdox toJS() replaces \p{^L} with a character class containing the full character range
	*/
	public function testConvertRegexp8c()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';

		$this->assertRegExp(
			'#^/\\[' . $unicodeRange . '\\]/$#D',
			(string) RegexpConvertor::toJS('#\\p{^L}#')
		);
	}

	/**
	* @testdox toJS() replaces \p{^L} with a character class equivalent to \PL
	*/
	public function testConvertRegexp8d()
	{
		$this->assertEquals(
			RegexpConvertor::toJS('#\\PL#'),
			RegexpConvertor::toJS('#\\p{^L}#')
		);
	}

	/**
	* @testdox toJS() replaces \P{^L} with a character class equivalent to \pL
	*/
	public function testConvertRegexp8e()
	{
		$this->assertEquals(
			RegexpConvertor::toJS('#\\pL#'),
			RegexpConvertor::toJS('#\\P{^L}#')
		);
	}

	/**
	* @testdox toJS() can convert regexps with lookahead assertions
	*/
	public function testConvertRegexpLookahead()
	{
		$this->assertEquals(
			'/(?=foo)|(?=bar)/',
			RegexpConvertor::toJS('#(?=foo)|(?=bar)#')
		);
	}

	/**
	* @testdox toJS() can convert regexps with negative lookahead assertions
	*/
	public function testConvertRegexpNegativeLookahead()
	{
		$this->assertEquals(
			'/(?!foo)|(?!bar)/',
			RegexpConvertor::toJS('#(?!foo)|(?!bar)#')
		);
	}

	/**
	* @testdox toJS() throws a RuntimeException on lookbehind assertions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Lookbehind assertions are not supported
	*/
	public function testConvertRegexpExceptionOnLookbehind()
	{
		RegexpConvertor::toJS('#(?<=foo)x#');
	}

	/**
	* @testdox toJS() throws a RuntimeException on negative lookbehind assertions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Negative lookbehind assertions are not supported
	*/
	public function testConvertRegexpExceptionOnNegativeLookbehind()
	{
		RegexpConvertor::toJS('#(?<!foo)x#');
	}

	/**
	* @testdox toJS() converts . to [\s\S] outside of character classes if the "s" modifier is set
	*/
	public function testConvertRegexpDotAll()
	{
		$this->assertEquals(
			'/foo([\\s\\S]*)bar/',
			RegexpConvertor::toJS('#foo(.*)bar#s')
		);
	}

	/**
	* @testdox toJS() does not convert . to [\s\S] if the "s" modifier is not set
	*/
	public function testConvertRegexpDotWithoutDotAll()
	{
		$this->assertEquals(
			'/foo(.*)bar/',
			RegexpConvertor::toJS('#foo(.*)bar#')
		);
	}

	/**
	* @testdox toJS() does not convert . inside of character classes
	*/
	public function testConvertRegexpDotInCharacterClasses()
	{
		$this->assertEquals(
			'/foo[.]+bar/',
			RegexpConvertor::toJS('#foo[.]+bar#s')
		);
	}

	/**
	* @testdox toJS() converts named captures into normal captures
	*/
	public function testConvertRegexpNamedCaptures()
	{
		$this->assertEquals(
			'/x([0-9]+)([a-z]+)x/',
			RegexpConvertor::toJS('#x(?<foo>[0-9]+)(?<bar>[a-z]+)x#')
		);
	}

	/**
	* @testdox toJS() replaces its second parameter with a list of capturing subpattern names
	*/
	public function testConvertRegexpNamedCapturesMap()
	{
		$regexp = RegexpConvertor::toJS('#x(?<foo>[0-9]+)(?<bar>[a-z]+)x#');

		$this->assertEquals(
			'/x([0-9]+)([a-z]+)x/',
			$regexp
		);

		$this->assertEquals(
			['', 'foo', 'bar'],
			$regexp->map
		);
	}

	/**
	* @testdox toJS() non-named capturing subpatterns leave an empty name in the map
	*/
	public function testConvertRegexpNamedCapturesMapIndices()
	{
		$regexp = RegexpConvertor::toJS('#x([0-9]+)(?<bar>[a-z]+)x#');

		$this->assertEquals(
			'/x([0-9]+)([a-z]+)x/',
			$regexp
		);

		$this->assertEquals(
			['', '', 'bar'],
			$regexp->map
		);
	}

	/**
	* @testdox toJS() handles duplicate subpattern names
	*/
	public function testConvertRegexpDuplicateNamedCapturesMap()
	{
		$regexp = RegexpConvertor::toJS('/(?J)(?<foo>[0-9]+)|(?<foo>[a-z]+)/');

		$this->assertEquals(
			'/([0-9]+)|([a-z]+)/',
			$regexp
		);

		$this->assertEquals(
			['', 'foo', 'foo'],
			$regexp->map
		);
	}

	/**
	* NOTE: this is a regression test
	* @testdox toJS() correctly converts /(?:foo)(?<z>bar)/ to /(?:foo)(bar)/
	*/
	public function testConvertNamedSubatternAfterNormalSubpattern()
	{
		$this->assertEquals(
			'/(?:foo)(bar)/',
			RegexpConvertor::toJS('/(?:foo)(?<z>bar)/')
		);
	}

	/**
	* @testdox toJS() converts atomic groups to non-capturing groups
	*/
	public function testConvertAtomicGrouping()
	{
		$this->assertEquals(
			'/(?:foo|bar)/',
			RegexpConvertor::toJS('#(?>foo|bar)#')
		);
	}

	/**
	* @testdox toJS('/x/') returns /x/
	*/
	public function testConvertDefaultNotGlobal()
	{
		$this->assertEquals('/x/', RegexpConvertor::toJS('/x/'));
	}

	/**
	* @testdox toJS('/x/', true) returns /x/g
	*/
	public function testConvertGlobal()
	{
		$this->assertEquals('/x/g', RegexpConvertor::toJS('/x/', true));
	}

	/**
	* @testdox toJS() throws a RuntimeException on unknown regexp features/tokens
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unknown token type 'unknown' encountered while parsing regexp
	* @runInSeparateProcess
	* @preserveGlobalState disabled
	* @group runs-in-separate-process
	*/
	public function testUnknownToken()
	{
		eval(
			'namespace s9e\\TextFormatter\\Configurator\\Helpers;

			class RegexpParser
			{
				public static function parse()
				{
					return \\' . __CLASS__ . '::dummyParse();
				}
			}'
		);

		RegexpConvertor::toJS('#x#');
	}

	public static function dummyParse()
	{
		return [
			'delimiter' => '#',
			'modifiers' => '',
			'regexp'    => 'foo',
			'tokens'    => [
				[
					'pos'     => 0,
					'len'     => 1,
					'type'    => 'unknown',
					'content' => ''
				]
			]
		];
	}

	/**
	* @testdox toJS() converts literal ASCII line terminators \r and \n
	*/
	public function testLiteralASCIILineTerminators()
	{
		$this->assertEquals(
			'/\\r\\n/',
			RegexpConvertor::toJS("/\r\n/")
		);
	}

	/**
	* @testdox toJS() converts literal Unicode line terminators \u2028 and \u2029
	*/
	public function testLiteralUnicodeLineTerminators()
	{
		$this->assertEquals(
			'/\\u2028\\u2029/',
			RegexpConvertor::toJS("/\xE2\x80\xA8\xE2\x80\xA9/")
		);
	}

	/**
	* @testdox toJS() escapes backslashes that precede literal line terminators
	*/
	public function testBackslashBeforeLiteralLineTerminators()
	{
		$this->assertEquals(
			'/\\\\\\n/',
			RegexpConvertor::toJS("/\\\n/")
		);
	}
}