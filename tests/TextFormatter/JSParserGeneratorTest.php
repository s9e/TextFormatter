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
	* @depends encodeArray_can_encode_arrays_to_objects
	*/
	public function encodeArray_can_preserve_a_key_of_an_array()
	{
		$arr = array(
			'a' => 1,
			'b' => 2
		);

		$this->assertSame(
			'{"a":1,b:2}',
			JSParserGenerator::encodeArray($arr, array(array('a')))
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

		$this->assertSame(
			'{a:{"z":1,b:2},b:2}',
			JSParserGenerator::encodeArray($arr, array(array('a', 'z')))
		);
	}

	/**
	* @ŧest
	* @depends encodeArray_can_preserve_a_key_of_a_nested_array
	*/
	public function test_encodeArray_preserves_keys_at_the_correct_depth()
	{
		$arr = array(
			'a' => array('a' => 1, 'b' => 2),
			'b' => 2
		);

		$this->assertSame(
			'{a:{"a":1,b:2},b:2}',
			JSParserGenerator::encodeArray($arr, array(array('a', 'a')))
		);
	}

	/**
	* @test
	* @depends encodeArray_can_preserve_a_key_of_an_array
	*/
	public function test_encodeArray_can_use_TRUE_as_a_wildcard()
	{
		$arr = array(
			'a' => array('a' => 1, 'b' => 2),
			'b' => array('a' => 1, 'b' => 2)
		);

		$this->assertSame(
			'{a:{"a":1,"b":2},b:{a:1,b:2}}',
			JSParserGenerator::encodeArray($arr, array(array('a', true)))
		);
	}

	/**
	* @test
	*/
	public function test_encodeArray_preserves_reserved_words()
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
}