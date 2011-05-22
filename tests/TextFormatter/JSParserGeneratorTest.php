<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\JSParserGenerator;

include_once __DIR__ . '/../Test.php';
include_once __DIR__ . '/../../src/TextFormatter/JSParserGenerator.php';

class JSParserGeneratorTest extends Test
{
	/**
	* @test
	*/
	public function encodeArray_can_encode_arrays_to_objects()
	{
		$arr = array(
			'a' => 1,
			'b' => 2
		);

		$this->assertSame(
			'{a:1,b:2}',
			JSParserGenerator::encodeArray($arr)
		);
	}

	/**
	* @test
	*/
	public function encodeArray_can_encode_arrays_to_Arrays()
	{
		$arr = array(1, 2);

		$this->assertSame(
			'[1,2]',
			JSParserGenerator::encodeArray($arr)
		);
	}

	/**
	* @test
	*/
	public function encodeArray_can_convert_regexp_strings_to_RegExp_objects()
	{
		$arr = array('/foo/');

		$struct = array(
			'isRegexp' => array(
				array(true)
			)
		);

		$this->assertContains(
			'new RegExp("foo")',
			JSParserGenerator::encodeArray($arr, $struct)
		);
	}


	/**
	* @test
	*/
	public function encodeArray_can_convert_regexp_strings_to_RegExp_objects_with_g_flag()
	{
		$arr = array('/foo/');

		$struct = array(
			'isGlobalRegexp' => array(
				array(true)
			)
		);

		$this->assertContains(
			'new RegExp("foo","g")',
			JSParserGenerator::encodeArray($arr, $struct)
		);
	}

	/**
	* @test
	* @depends encodeArray_can_encode_arrays_to_Arrays
	*/
	public function encode_encodes_booleans_to_0_and_1()
	{
		$this->assertSame(
			'[1,0,1]',
			JSParserGenerator::encode(array(true, false, true))
		);
	}

	/**
	* @test
	* @depends encodeArray_can_encode_arrays_to_objects
	*/
	public function encodeArray_can_preserve_a_key_of_an_array()
	{
		$arr = array(
			'a' => 1,
			'b' => 2
		);

		$struct = array(
			'preserveKeys' => array(
				array('a')
			)
		);

		$this->assertSame(
			'{"a":1,b:2}',
			JSParserGenerator::encodeArray($arr, $struct)
		);
	}

	/**
	* @test
	* @depends encodeArray_can_preserve_a_key_of_an_array
	*/
	public function encodeArray_can_preserve_a_key_of_a_nested_array()
	{
		$arr = array(
			'a' => array('z' => 1, 'b' => 2),
			'b' => 2
		);

		$struct = array(
			'preserveKeys' => array(
				array('a', 'z')
			)
		);

		$this->assertSame(
			'{a:{"z":1,b:2},b:2}',
			JSParserGenerator::encodeArray($arr, $struct)
		);
	}

	/**
	* @ŧest
	* @depends encodeArray_can_preserve_a_key_of_a_nested_array
	*/
	public function encodeArray_preserves_keys_at_the_correct_depth()
	{
		$arr = array(
			'a' => array('a' => 1, 'b' => 2),
			'b' => 2
		);

		$struct = array(
			'preserveKeys' => array(
				array('a', 'a')
			)
		);

		$this->assertSame(
			'{a:{"a":1,b:2},b:2}',
			JSParserGenerator::encodeArray($arr, $struct)
		);
	}

	/**
	* @test
	* @depends encodeArray_can_preserve_a_key_of_an_array
	*/
	public function encodeArray_can_use_TRUE_as_a_wildcard()
	{
		$arr = array(
			'a' => array('a' => 1, 'b' => 2),
			'b' => array('a' => 1, 'b' => 2)
		);

		$struct = array(
			'preserveKeys' => array(
				array('a', true)
			)
		);

		$this->assertSame(
			'{a:{"a":1,"b":2},b:{a:1,b:2}}',
			JSParserGenerator::encodeArray($arr, $struct)
		);
	}

	/**
	* @test
	*/
	public function encodeArray_preserves_reserved_words()
	{
		$arr = array(
			'a'    => 1,
			'with' => 2
		);

		$this->assertSame(
			'{a:1,"with":2}',
			JSParserGenerator::encodeArray($arr)
		);
	}

	/**
	* @test
	* @dataProvider deadCodeProvider
	*/
	public function Useless_code_is_removed_from_the_source($funcNames, $tagConfig)
	{
		$regexps = array();

		foreach ((array) $funcNames as $funcName)
		{
			$regexps[$funcName] = '#function ' . $funcName . '\\([^\\)]*\\)\\s*\\{\\s*\\}#';
		}

		$jspg = new JSParserGenerator($this->cb);

		$this->cb->addTag('A');
		$this->cb->addTag('B');

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
		$this->cb->addTag('A', $tagConfig);

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
			array('closeAscendant',   array('rules' => array('closeAscendant' => array('B')))),
			array('requireParent',    array('rules' => array('requireParent' => array('B')))),
			array('requireAscendant', array('rules' => array('requireAscendant' => array('B')))),

			// attributes
			array(
				'currentTagRequiresMissingAttribute',
				array('attrs' => array('foo' => array('type' => 'int', 'isRequired' => true)))
			),
			array(
				array('filterAttributes', 'filter'),
				array('attrs' => array('foo' => array('type' => 'int')))
			),
			array(
				'splitCompoundAttributes',
				array('attrs' => array('foo' => array('type' => 'compound')))
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
		);
	}
}