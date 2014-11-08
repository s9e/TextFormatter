<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use DOMDocument;
use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\AVTHelper
*/
class AVTHelperTest extends Test
{
	/**
	* @testdox parse() tests
	* @dataProvider getParseTests
	*/
	public function testParse($attrValue, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$this->assertSame($expected, AVTHelper::parse($attrValue));
	}

	public function getParseTests()
	{
		return array(
			array(
				'',
				array()
			),
			array(
				'foo',
				array(
					array('literal', 'foo')
				)
			),
			array(
				'foo {@bar} baz',
				array(
					array('literal',    'foo '),
					array('expression', '@bar'),
					array('literal',    ' baz')
				)
			),
			array(
				'foo {{@bar}} baz',
				array(
					array('literal', 'foo '),
					array('literal', '{'),
					array('literal', '@bar} baz')
				)
			),
			array(
				'foo {@bar}{baz} quux',
				array(
					array('literal',    'foo '),
					array('expression', '@bar'),
					array('expression', 'baz'),
					array('literal',    ' quux')
				)
			),
			array(
				'foo {"bar"} baz',
				array(
					array('literal',    'foo '),
					array('expression', '"bar"'),
					array('literal',    ' baz')
				)
			),
			array(
				"foo {'bar'} baz",
				array(
					array('literal',    'foo '),
					array('expression', "'bar'"),
					array('literal',    ' baz')
				)
			),
			array(
				'foo {"\'bar\'"} baz',
				array(
					array('literal',    'foo '),
					array('expression', '"\'bar\'"'),
					array('literal',    ' baz')
				)
			),
			array(
				'foo {"{bar}"} baz',
				array(
					array('literal',    'foo '),
					array('expression', '"{bar}"'),
					array('literal',    ' baz')
				)
			),
			array(
				'foo {"bar} baz',
				new RuntimeException('Unterminated XPath expression')
			),
			array(
				'foo {bar',
				new RuntimeException('Unterminated XPath expression')
			),
			array(
				'<foo> {"<bar>"} &amp;',
				array(
					array('literal',    '<foo> '),
					array('expression', '"<bar>"'),
					array('literal',    ' &amp;')
				)
			),
		);
	}

	/**
	* @testdox serialize() tests
	* @dataProvider getSerializeTests
	*/
	public function testSerialize($tokens, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$this->assertSame($expected, AVTHelper::serialize($tokens));
	}

	public function getSerializeTests()
	{
		return array(
			array(
				array(array('literal', 'foo')),
				'foo'
			),
			array(
				array(
					array('literal',    'foo '),
					array('expression', '@bar'),
					array('literal',    ' baz')
				),
				'foo {@bar} baz'
			),
			array(
				array(
					array('literal', 'foo '),
					array('literal', '{'),
					array('literal', '@bar} baz')
				),
				'foo {{@bar}} baz'
			),
			array(
				array(
					array('literal',    'foo '),
					array('expression', '@bar'),
					array('expression', 'baz'),
					array('literal',    ' quux')
				),
				'foo {@bar}{baz} quux'
			),
			array(
				array(array('unknown', 'foo')),
				new RuntimeException('Unknown token type')
			),
			array(
				array(
					array('literal',    '<foo> '),
					array('expression', '"<bar>"'),
					array('literal',    ' &amp;')
				),
				'<foo> {"<bar>"} &amp;',
			)
		);
	}

	/**
	* @testdox replace() tests
	* @dataProvider getReplaceTests
	*/
	public function testReplace($xml, $callback, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		AVTHelper::replace($dom->documentElement->getAttributeNode('x'), $callback);

		$this->assertSame($expected, $dom->saveXML($dom->documentElement));
	}

	public function getReplaceTests()
	{
		return array(
			array(
				'<x x="&quot;AT&amp;T&quot;"/>',
				function ($token)
				{
					return $token;
				},
				'<x x="&quot;AT&amp;T&quot;"/>',
			),
			array(
				'<x x="{@foo}"/>',
				function ($token)
				{
					return $token;
				},
				'<x x="{@foo}"/>',
			),
			array(
				'<x x="X{@X}X"/>',
				function ($token)
				{
					return array('literal', 'x');
				},
				'<x x="xxx"/>',
			),
		);
	}
}