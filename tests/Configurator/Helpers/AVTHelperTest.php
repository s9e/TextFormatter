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
		$this->assertSame($expected, AVTHelper::parse($attrValue));
	}

	public static function getParseTests()
	{
		return [
			[
				'',
				[]
			],
			[
				'foo',
				[
					['literal', 'foo']
				]
			],
			[
				'foo {@bar} baz',
				[
					['literal',    'foo '],
					['expression', '@bar'],
					['literal',    ' baz']
				]
			],
			[
				'foo {{@bar}} baz',
				[
					['literal', 'foo {@bar} baz']
				]
			],
			[
				'foo {@bar}{baz} quux',
				[
					['literal',    'foo '],
					['expression', '@bar'],
					['expression', 'baz'],
					['literal',    ' quux']
				]
			],
			[
				'foo {"bar"} baz',
				[
					['literal',    'foo '],
					['expression', '"bar"'],
					['literal',    ' baz']
				]
			],
			[
				"foo {'bar'} baz",
				[
					['literal',    'foo '],
					['expression', "'bar'"],
					['literal',    ' baz']
				]
			],
			[
				'foo {"\'bar\'"} baz',
				[
					['literal',    'foo '],
					['expression', '"\'bar\'"'],
					['literal',    ' baz']
				]
			],
			[
				'foo {"{bar}"} baz',
				[
					['literal',    'foo '],
					['expression', '"{bar}"'],
					['literal',    ' baz']
				]
			],
			[
				'foo {"bar} baz',
				[
					['literal', 'foo '],
					['literal', '{"bar} baz']
				]
			],
			[
				'foo {bar',
				[
					['literal', 'foo '],
					['literal', '{bar']
				]
			],
			[
				'<foo> {"<bar>"} &amp;',
				[
					['literal',    '<foo> '],
					['expression', '"<bar>"'],
					['literal',    ' &amp;']
				]
			],
		];
	}

	/**
	* @testdox serialize() tests
	* @dataProvider getSerializeTests
	*/
	public function testSerialize($tokens, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());

		}

		$this->assertSame($expected, AVTHelper::serialize($tokens));
	}

	public static function getSerializeTests()
	{
		return [
			[
				[['literal', 'foo']],
				'foo'
			],
			[
				[
					['literal',    'foo '],
					['expression', '@bar'],
					['literal',    ' baz']
				],
				'foo {@bar} baz'
			],
			[
				[
					['literal', 'foo '],
					['literal', '{'],
					['literal', '@bar} baz']
				],
				'foo {{@bar}} baz'
			],
			[
				[
					['literal',    'foo '],
					['expression', '@bar'],
					['expression', 'baz'],
					['literal',    ' quux']
				],
				'foo {@bar}{baz} quux'
			],
			[
				[['unknown', 'foo']],
				new RuntimeException('Unknown token type')
			],
			[
				[
					['literal',    '<foo> '],
					['expression', '"<bar>"'],
					['literal',    ' &amp;']
				],
				'<foo> {"<bar>"} &amp;',
			]
		];
	}

	/**
	* @testdox replace() tests
	* @dataProvider getReplaceTests
	*/
	public function testReplace($xml, $callback, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());

		}

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		AVTHelper::replace($dom->documentElement->getAttributeNode('x'), $callback);

		$this->assertSame($expected, $dom->saveXML($dom->documentElement));
	}

	public static function getReplaceTests()
	{
		return [
			[
				'<x x="&quot;AT&amp;T&quot;"/>',
				function ($token)
				{
					return $token;
				},
				'<x x="&quot;AT&amp;T&quot;"/>',
			],
			[
				'<x x="{@foo}"/>',
				function ($token)
				{
					return $token;
				},
				'<x x="{@foo}"/>',
			],
			[
				'<x x="X{@X}X"/>',
				function ($token)
				{
					return ['literal', 'x'];
				},
				'<x x="xxx"/>',
			],
		];
	}

	/**
	* @testdox toXSL() tests
	* @dataProvider getToXSLTests
	*/
	public function testToXSL($attrValue, $expected)
	{
		$this->assertSame($expected, AVTHelper::toXSL($attrValue));
	}

	public static function getToXSLTests()
	{
		return [
			[
				'',
				''
			],
			[
				'foo',
				'foo'
			],
			[
				'{@foo}',
				'<xsl:value-of select="@foo"/>'
			],
			[
				'{@foo}bar',
				'<xsl:value-of select="@foo"/>bar'
			],
			[
				' {@foo} ',
				'<xsl:text> </xsl:text><xsl:value-of select="@foo"/><xsl:text> </xsl:text>'
			],
			[
				"{'\"'}",
				'<xsl:value-of select="\'&quot;\'"/>'
			],
			[
				'{"\'"}',
				'<xsl:value-of select="&quot;\'&quot;"/>'
			],
			[
				"{'<>'}",
				'<xsl:value-of select="\'&lt;&gt;\'"/>'
			],
			[
				'<"\'>',
				'&lt;"\'&gt;'
			],
		];
	}
}