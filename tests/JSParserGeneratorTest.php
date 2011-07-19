<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\JSParserGenerator;

include_once __DIR__ . '/Test.php';
include_once __DIR__ . '/../src/JSParserGenerator.php';

class JSParserGeneratorTest extends Test
{
	protected function encodeArray(array $arr)
	{
		return $this->call(
			's9e\\TextFormatter\\JSParserGenerator',
			'encodeArray',
			func_get_args()
		);
	}

	protected function encodeConfig(array $config)
	{
		return $this->call(
			's9e\\TextFormatter\\JSParserGenerator',
			'encodeConfig',
			func_get_args()
		);
	}

	/**
	* @testdox convertRegexp() can convert plain regexps
	*/
	public function testConvertRegexp1()
	{
		$this->assertEquals(
			'/foo/',
			JSParserGenerator::convertRegexp('#foo#')
		);
	}

	/**
	* @testdox convertRegexp() escapes forward slashes
	*/
	public function testConvertRegexpEscape()
	{
		$this->assertEquals(
			'/fo\\/o/',
			JSParserGenerator::convertRegexp('#fo/o#')
		);
	}

	/**
	* @testdox convertRegexp() does not double-escape forward slashes that are already escaped
	*/
	public function testConvertRegexpNoDoubleEscape()
	{
		$this->assertEquals(
			'/fo\\/o/',
			JSParserGenerator::convertRegexp('#fo\\/o#')
		);
	}

	/**
	* @testdox convertRegexp() does not "eat" backslashes while escaping forward slashes
	*/
	public function testConvertRegexpDoesNotEatEscapedBackslashes()
	{
		$this->assertEquals(
			'/fo\\\\\\/o/',
			JSParserGenerator::convertRegexp('#fo\\\\/o#')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with the "i" modifier
	*/
	public function testConvertRegexp2()
	{
		$this->assertEquals(
			'/foo/i',
			JSParserGenerator::convertRegexp('#foo#i')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with capturing subpatterns
	*/
	public function testConvertRegexp3()
	{
		$this->assertEquals(
			'/f(o)o/',
			JSParserGenerator::convertRegexp('#f(o)o#')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with non-capturing subpatterns
	*/
	public function testConvertRegexp4()
	{
		$this->assertEquals(
			'/f(?:o)o/',
			JSParserGenerator::convertRegexp('#f(?:o)o#')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with non-capturing subpatterns with a quantifier
	*/
	public function testConvertRegexp5()
	{
		$this->assertEquals(
			'/f(?:oo)+/',
			JSParserGenerator::convertRegexp('#f(?:oo)+#')
		);
	}

	/**
	* @testdox convertRegexp() throws a RuntimeException on options (?i)
	* @expectedException RuntimeException
	* @expectedExceptionMessage Regexp options are not supported
	*/
	public function testConvertRegexpException1()
	{
		JSParserGenerator::convertRegexp('#(?i)x#');
	}

	/**
	* @testdox convertRegexp() throws a RuntimeException on subpattern options (?i:)
	* @expectedException RuntimeException
	* @expectedExceptionMessage Subpattern options are not supported
	*/
	public function testConvertRegexpException2()
	{
		JSParserGenerator::convertRegexp('#(?i:x)#');
	}

	/**
	* @testdox convertRegexp() can convert regexps with character classes with a quantifier
	*/
	public function testConvertRegexp6()
	{
		$this->assertEquals(
			'/[a-z]+/',
			JSParserGenerator::convertRegexp('#[a-z]+#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \pL with the full character range in character classes
	*/
	public function testConvertRegexp7()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[0-9' . $unicodeRange . '\\]/$#D',
			JSParserGenerator::convertRegexp('#[0-9\\pL]#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \p{L} with the full character range in character classes
	*/
	public function testConvertRegexp7b()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[0-9' . $unicodeRange . '\\]/$#D',
			JSParserGenerator::convertRegexp('#[0-9\\p{L}]#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \pL outside of character classes with a character class containing the full character range
	*/
	public function testConvertRegexp8()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]00\\[' . $unicodeRange . '\\]/$#D',
			JSParserGenerator::convertRegexp('#\\pL00\\pL#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \p{L} outside of character classes with a character class containing the full character range
	*/
	public function testConvertRegexp8b()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';
		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]00\\[' . $unicodeRange . '\\]/$#D',
			JSParserGenerator::convertRegexp('#\\p{L}00\\p{L}#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \p{^L} with a character class containing the full character range
	*/
	public function testConvertRegexp8c()
	{
		$unicodeRange = '(?:[a-zA-Z]-?)*(?:\\\\u[0-9A-F]{4}-?)*';

		$this->assertRegexp(
			'#^/\\[' . $unicodeRange . '\\]/$#D',
			JSParserGenerator::convertRegexp('#\\p{^L}#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \p{^L} with a character class equivalent to \PL
	*/
	public function testConvertRegexp8d()
	{
		$this->assertSame(
			JSParserGenerator::convertRegexp('#\\PL#'),
			JSParserGenerator::convertRegexp('#\\p{^L}#')
		);
	}

	/**
	* @testdox convertRegexp() replaces \P{^L} with a character class equivalent to \pL
	*/
	public function testConvertRegexp8e()
	{
		$this->assertSame(
			JSParserGenerator::convertRegexp('#\\pL#'),
			JSParserGenerator::convertRegexp('#\\P{^L}#')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with lookahead assertions
	*/
	public function testConvertRegexpLookahead()
	{
		$this->assertEquals(
			'/(?=foo)|(?=bar)/',
			JSParserGenerator::convertRegexp('#(?=foo)|(?=bar)#')
		);
	}

	/**
	* @testdox convertRegexp() can convert regexps with negative lookahead assertions
	*/
	public function testConvertRegexpNegativeLookahead()
	{
		$this->assertEquals(
			'/(?!foo)|(?!bar)/',
			JSParserGenerator::convertRegexp('#(?!foo)|(?!bar)#')
		);
	}

	/**
	* @testdox convertRegexp() throws a RuntimeException on lookbehind assertions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Lookbehind assertions are not supported
	*/
	public function testConvertRegexpExceptionOnLookbehind()
	{
		JSParserGenerator::convertRegexp('#(?<=foo)x#');
	}

	/**
	* @testdox convertRegexp() throws a RuntimeException on negative lookbehind assertions
	* @expectedException RuntimeException
	* @expectedExceptionMessage Negative lookbehind assertions are not supported
	*/
	public function testConvertRegexpExceptionOnNegativeLookbehind()
	{
		JSParserGenerator::convertRegexp('#(?<!foo)x#');
	}

	/**
	* @testdox convertRegexp() converts . to [\s\S] outside of character classes is the "s" modifier is set
	*/
	public function testConvertRegexpDotAll()
	{
		$this->assertEquals(
			'/foo([\\s\\S]*)bar/',
			JSParserGenerator::convertRegexp('#foo(.*)bar#s')
		);
	}

	/**
	* @testdox convertRegexp() does not convert . to [\s\S] if the "s" modifier is not set
	*/
	public function testConvertRegexpDotWithoutDotAll()
	{
		$this->assertEquals(
			'/foo(.*)bar/',
			JSParserGenerator::convertRegexp('#foo(.*)bar#')
		);
	}

	/**
	* @testdox convertRegexp() does not convert . inside of character classes
	*/
	public function testConvertRegexpDotInCharacterClasses()
	{
		$this->assertEquals(
			'/foo[.]+bar/',
			JSParserGenerator::convertRegexp('#foo[.]+bar#s')
		);
	}

	/**
	* @testdox convertRegexp() converts named captures into normal captures
	*/
	public function testConvertRegexpNamedCaptures()
	{
		$this->assertEquals(
			'/x([0-9]+)([a-z]+)x/',
			JSParserGenerator::convertRegexp('#x(?<foo>[0-9]+)(?<bar>[a-z]+)x#', $map)
		);
	}

	/**
	* @testdox convertRegexp() replaces its second parameter with an array that maps named captures to their index
	*/
	public function testConvertRegexpNamedCapturesMap()
	{
		$map = null;

		$this->assertEquals(
			'/x([0-9]+)([a-z]+)x/',
			JSParserGenerator::convertRegexp('#x(?<foo>[0-9]+)(?<bar>[a-z]+)x#', $map)
		);

		$this->assertEquals(
			array('foo' => 1, 'bar' => 2),
			$map
		);
	}

	/**
	* @testdox encodeArray() can encode arrays to objects
	*/
	public function test_encodeArray_can_encode_arrays_to_objects()
	{
		$arr = array(
			'a' => 1,
			'b' => 2
		);

		$this->assertSame(
			'{a:1,b:2}',
			$this->encodeArray($arr)
		);
	}

	/**
	* @testdox encodeArray() can encode arrays to Arrays
	*/
	public function test_encodeArray_can_encode_arrays_to_Arrays()
	{
		$arr = array(1, 2);

		$this->assertSame(
			'[1,2]',
			$this->encodeArray($arr)
		);
	}

	/**
	* @testdox encodeArray() can convert regexp strings to RegExp objects
	*/
	public function test_encodeArray_can_convert_regexp_strings_to_RegExp_objects()
	{
		$arr = array('/foo/');

		$meta = array(
			'isRegexp' => array(
				array(true)
			)
		);

		$this->assertContains(
			'/foo/',
			$this->encodeArray($arr, $meta)
		);
	}

	/**
	* @testdox encodeArray() can convert regexp strings to RegExp objects with g flag
	*/
	public function test_encodeArray_can_convert_regexp_strings_to_RegExp_objects_with_g_flag()
	{
		$arr = array('/foo/');

		$meta = array(
			'isGlobalRegexp' => array(
				array(true)
			)
		);

		$this->assertContains(
			'/foo/g',
			$this->encodeArray($arr, $meta)
		);
	}

	/**
	* @testdox encode() encodes booleans to 0 and 1
	* @depends test_encodeArray_can_encode_arrays_to_Arrays
	*/
	public function test_encode_encodes_booleans_to_0_and_1()
	{
		$this->assertSame(
			'[1,0,1]',
			JSParserGenerator::encode(array(true, false, true))
		);
	}

	/**
	* @testdox encodeArray() can preserve a key of an array
	* @depends test_encodeArray_can_encode_arrays_to_objects
	*/
	public function test_encodeArray_can_preserve_a_key_of_an_array()
	{
		$arr = array(
			'a' => 1,
			'b' => 2
		);

		$meta = array(
			'preserveKeys' => array(
				array('a')
			)
		);

		$this->assertSame(
			'{"a":1,b:2}',
			$this->encodeArray($arr, $meta)
		);
	}

	/**
	* @testdox encodeArray() can preserve a key of a nested array
	* @depends test_encodeArray_can_preserve_a_key_of_an_array
	*/
	public function test_encodeArray_can_preserve_a_key_of_a_nested_array()
	{
		$arr = array(
			'a' => array('z' => 1, 'b' => 2),
			'b' => 2
		);

		$meta = array(
			'preserveKeys' => array(
				array('a', 'z')
			)
		);

		$this->assertSame(
			'{a:{"z":1,b:2},b:2}',
			$this->encodeArray($arr, $meta)
		);
	}

	/**
	* @testdox encodeArray() preserves keys at the correct depth
	* @depends test_encodeArray_can_preserve_a_key_of_a_nested_array
	*/
	public function test_encodeArray_preserves_keys_at_the_correct_depth()
	{
		$arr = array(
			'a' => array('a' => 1, 'b' => 2),
			'b' => 2
		);

		$meta = array(
			'preserveKeys' => array(
				array('a', 'a')
			)
		);

		$this->assertSame(
			'{a:{"a":1,b:2},b:2}',
			$this->encodeArray($arr, $meta)
		);
	}

	/**
	* @testdox encodeArray() can use TRUE as a wildcard
	* @depends test_encodeArray_can_preserve_a_key_of_an_array
	*/
	public function test_encodeArray_can_use_TRUE_as_a_wildcard()
	{
		$arr = array(
			'a' => array('a' => 1, 'b' => 2),
			'b' => array('a' => 1, 'b' => 2)
		);

		$meta = array(
			'preserveKeys' => array(
				array('a', true)
			)
		);

		$this->assertSame(
			'{a:{"a":1,"b":2},b:{a:1,b:2}}',
			$this->encodeArray($arr, $meta)
		);
	}

	/**
	* @testdox encodeArray() preserves reserved words
	*/
	public function test_encodeArray_preserves_reserved_words()
	{
		$arr = array(
			'a'    => 1,
			'with' => 2
		);

		$this->assertSame(
			'{a:1,"with":2}',
			$this->encodeArray($arr)
		);
	}

	/**
	* @testdox encodeArray() can preserve raw JS
	*/
	public function test_encodeArray_can_preserve_raw_JS()
	{
		$arr = array(
			'a' => 1,
			'b' => 'foo()'
		);

		$meta = array(
			'isRawJS' => array(
				array('b')
			)
		);

		$this->assertSame(
			'{a:1,b:foo()}',
			$this->encodeArray($arr, $meta)
		);
	}

	/**
	* @test
	* @dataProvider deadCodeProvider
	*/
	public function Useless_code_is_removed_from_the_source($funcNames, $keepConfig, $removeConfig = array())
	{
		$regexps = array();

		foreach ((array) $funcNames as $funcName)
		{
			$regexps[$funcName] = '#function ' . $funcName . '\\([^\\)]*\\)\\s*\\{\\s*\\}#';
		}

		$this->cb->addTag('B');
		$this->cb->addTag('A', $removeConfig);

		// First we test that the code is removed by default
		foreach ($regexps as $funcName => $regexp)
		{
			$this->assertRegExp(
				$regexp,
				$this->cb->getJSParser(array('compilation' => 'none')),
				$funcName . ' did not get removed'
			);
		}

		$this->cb->removeTag('A');
		$this->cb->addTag('A', $keepConfig);

		// Then we make sure it's not applicable
		foreach ($regexps as $funcName => $regexp)
		{
			$this->assertNotRegExp(
				$regexp,
				$this->cb->getJSParser(array('compilation' => 'none')),
				$funcName . ' incorrectly got removed'
			);
		}
	}

	public function deadCodeProvider()
	{
		return array(
			// rules
			array('closeParent',      array('rules' => array('closeParent' => array('B')))),
			array('closeAncestor',   array('rules' => array('closeAncestor' => array('B')))),
			array('requireParent',    array('rules' => array('requireParent' => array('B')))),
			array('requireAncestor', array('rules' => array('requireAncestor' => array('B')))),

			// attributes
			array(
				'currentTagRequiresMissingAttribute',
				array('attrs' => array('foo' => array('type' => 'int', 'isRequired' => true))),
				array('attrs' => array('foo' => array('type' => 'int', 'isRequired' => false)))
			),
			array(
				array('filterAttributes', 'filter'),
				array('attrs' => array('foo' => array('type' => 'int')))
			),
			array(
				'splitCompoundAttributes',
				array('attrs' => array('foo' => array('type' => 'compound', 'regexp' => '##')))
			),
			array(
				'addDefaultAttributeValuesToCurrentTag',
				array('attrs' => array('foo' => array('type' => 'int', 'defaultValue' => 42)))
			),

			// callbacks
			array(
				array('applyCallback', 'applyTagPreFilterCallbacks'),
				array('preFilter' => array(array('callback' => 'array_unique')))
			),
			array(
				array('applyCallback', 'applyTagPostFilterCallbacks'),
				array('postFilter' => array(array('callback' => 'array_unique')))
			),
			array(
				array('applyCallback', 'applyAttributePreFilterCallbacks'),
				array(
					'attrs' => array(
						'foo' => array(
							'type' => 'int',
							'preFilter' => array(array('callback' => 'trim'))
						)
					)
				)
			),
			array(
				array('applyCallback', 'applyAttributePostFilterCallbacks'),
				array(
					'attrs' => array(
						'foo' => array(
							'type' => 'int',
							'postFilter' => array(array('callback' => 'trim'))
						)
					)
				)
			),

			// whitespace trimming
			array(
				'addTrimmingInfoToTag',
				array('trimBefore' => true)
			),
			array(
				'addTrimmingInfoToTag',
				array('trimAfter' => true)
			),
			array(
				'addTrimmingInfoToTag',
				array('ltrimContent' => true)
			),
			array(
				'addTrimmingInfoToTag',
				array('rtrimContent' => true)
			),
		);
	}

	/**
	* @testdox generateFiltersConfig() returns allowedSchemes regexp as an object
	*/
	public function test_generateFiltersConfig_returns_allowedSchemes_regexp_as_an_object()
	{
		$this->call($this->jspg, 'init');

		$this->assertContains(
			'allowedSchemes:/^https?$/i',
			$this->call($this->jspg, 'generateFiltersConfig')
		);
	}

	/**
	* @testdox generateFiltersConfig() returns disallowedHosts regexp as an object
	*/
	public function test_generateFiltersConfig_returns_disallowedHosts_regexp_as_an_object()
	{
		$this->cb->disallowHost('example.com');
		$this->call($this->jspg, 'init');

		$this->assertContains(
			'disallowedHosts:/',
			$this->call($this->jspg, 'generateFiltersConfig')
		);
	}

	/**
	* @testdox generateFiltersConfig() converts unsupported lookbehind assertions from disallowedHosts regexp
	* @depends test_generateFiltersConfig_returns_disallowedHosts_regexp_as_an_object
	*/
	public function test_generateFiltersConfig_converts_unsupported_lookbehind_assertions_from_disallowedHosts_regexp()
	{
		$this->cb->disallowHost('example.com');
		$this->call($this->jspg, 'init');

		$this->assertContains(
			'/(?:^|\\.)example\\.com$/i',
			$this->call($this->jspg, 'generateFiltersConfig')
		);
	}

	/**
	* @testdox encodeConfig() removes parserClassName from config
	* @depends test_encodeArray_can_encode_arrays_to_objects
	*/
	public function test_encodeConfig_removes_parserClassName_from_config()
	{
		$this->assertSame(
			'{foo:1}',
			$this->encodeConfig(
				array(
					'parserClassName' => 'foo',
					'foo' => 1
				),
				array()
			)
		);
	}

	/**
	* @testdox encodeConfig() removes parserFilepath from config
	* @depends test_encodeArray_can_encode_arrays_to_objects
	*/
	public function test_encodeConfig_removes_parserFilepath_from_config()
	{
		$this->assertSame(
			'{foo:1}',
			$this->encodeConfig(
				array(
					'parserFilepath' => 'foo',
					'foo' => 1
				),
				array()
			)
		);
	}

	/**
	* @testdox encodeConfig() convert scalar regexp to a RegExp object with g flag
	* @depends test_encodeArray_can_encode_arrays_to_objects
	*/
	public function test_encodeConfig_convert_scalar_regexp_to_a_RegExp_object_with_g_flag()
	{
		$this->assertSame(
			'{regexp:/foo/g}',
			$this->encodeConfig(
				array(
					'regexp' => '#foo#'
				),
				array()
			)
		);
	}

	/**
	* @testdox encodeConfig() convert array regexp to an object with RegExp objects with g flag as properties
	* @depends test_encodeArray_can_encode_arrays_to_objects
	*/
	public function test_encodeConfig_convert_array_regexp_to_an_object_with_RegExp_objects_with_g_flag_as_properties()
	{
		$this->assertSame(
			'{regexp:{bar:/bar/g,baz:/baz/g}}',
			$this->encodeConfig(
				array(
					'regexp' => array(
						'bar' => '#bar#',
						'baz' => '#baz#'
					)
				),
				array()
			)
		);
	}

	/**
	* @test
	*/
	public function Injects_plugins_parsers_into_source()
	{
		$this->cb->loadPlugin('Autolink');

		$jsParser = $this->jspg->get();

		$this->assertContains(
			'parser:function(',
			$jsParser
		);
	}

	/**
	* @test
	*/
	public function Injects_plugins_configs_into_source()
	{
		$this->cb->loadPlugin('Autolink');

		$jsParser = $this->jspg->get();

		$this->assertContains(
			'pluginsConfig = {"Autolink":{',
			$jsParser
		);
	}

	/**
	* @testdox replaceConstant() throws an exception if no replacement occurs
	* @expectedException RuntimeException
	* @expectedExceptionMessage Tried to replace constant UNKNOWN, 0 occurences found
	*/
	public function testReplaceConstantFailZeroMatch()
	{
		$this->call($this->jspg, 'init');
		$this->call($this->jspg, 'replaceConstant', array('UNKNOWN', 2));
	}

	/**
	* @testdox A regexp map is created for compound attributes
	*/
	public function testCompoundAttributesHaveARegexpMap()
	{
		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'x', 'compound', array(
			'regexp' => '#^(?<width>[0-9]+),(?<height>[0-9]+)$#'
		));

		$this->call($this->jspg, 'init');

		$this->assertContains(
			'attrs:{"x":{isRequired:0,regexp:/^([0-9]+),([0-9]+)$/,type:"compound",regexpMap:{width:1,height:2}}}',
			$this->call($this->jspg, 'generateTagsConfig')
		);
	}

	/**
	* @testdox Optimization hint HINT_REGEXP_REPLACEWITH is false by default
	*/
	public function test_Optimization_hint_REGEXP_REPLACEWITH_false_by_default()
	{
		$this->assertRegexp(
			'#HINT_REGEXP_REPLACEWITH\\s*=\\s*false#',
			$this->cb->getJSParser()
		);
	}

	/**
	* @testdox Optimization hint HINT_REGEXP_REPLACEWITH is true if any "regexp" attribute has a "replaceWith" option set
	*/
	public function test_Optimization_hint_REGEXP_REPLACEWITH()
	{
		$this->cb->addTag('X');
		$this->cb->addTagAttribute('X', 'x', 'regexp', array('replaceWith' => 'xx'));

		$this->assertRegexp(
			'#HINT_REGEXP_REPLACEWITH\\s*=\\s*true#',
			$this->cb->getJSParser()
		);
	}

	/**
	* @testdox The source is minified with Google Closure Compiler if "compilation" is not set to "none"
	*/
	public function test_Closure_Compiler()
	{
		$this->jspg->closureCompilerURL = 'data:text/plain,SUCCESS';

		$this->assertSame(
			'SUCCESS',
			$this->jspg->get(array('compilation' => 'ADVANCED_OPTIMIZATIONS'))
		);
	}

	/**
	* @testdox Log types can be selectively disabled
	*/
	public function testDisableLog()
	{
		$js = $this->cb->getJSParser(array(
			'disableLogTypes' => array('debug')
		));

		$this->assertNotRegexp(
			'#(?<!0&&)log\\(.debug#',
			$js,
			'Not all "debug" messages have been disabled'
		);

		$this->assertNotRegexp(
			'#0&&log\\(.(?!debug)#',
			$js,
			'Some non-"debug" messages have been disabled'
		);
	}

	/**
	* @testdox The "unsafeMinification" option indiscriminately renames all occurences of properties that share their name with DOM properties, except if their variable's names ends with "Node" or "Attr"
	*/
	public function testUnsafeMinification()
	{
		$js = $this->cb->getJSParser(array(
			'unsafeMinification' => true
		));

		$names= implode('|', array(
			'id',
			'name',
			'type',
			'rules',
			'defaultValue',
			'tagName',
			'attrName'
		));

		$this->assertNotRegexp(
			'#(?<!Node|Attr)\\.(?:' . $names . ')#',
			$js,
			'A reserved name is used by a property of a variable whose name does not end with "Node" or "Attr"'
		);
	}
}