<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Utils;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Utils
*/
class UtilsTest extends Test
{
	/**
	* @testdox encodeUnicodeSupplementaryCharacters() tests
	* @dataProvider getEncodeUnicodeSupplementaryCharactersTests
	*/
	public function testEncodeUnicodeSupplementaryCharacters($original, $expected)
	{
		$this->assertSame($expected, Utils::encodeUnicodeSupplementaryCharacters($original));
	}

	public function getEncodeUnicodeSupplementaryCharactersTests()
	{
		return [
			[
				'😀😁',
				'&#128512;&#128513;'
			],
		];
	}

	/**
	* @testdox getAttributeValues() tests
	* @dataProvider getGetAttributeValuesTests
	*/
	public function testGetAttributeValues($xml, $tagName, $attrName, $expected)
	{
		Utils::getAttributeValues($xml, $tagName, $attrName);
		$this->assertSame($expected, Utils::getAttributeValues($xml, $tagName, $attrName));
	}

	public function getGetAttributeValuesTests()
	{
		return [
			[
				'<t>Plain text</t>',
				'X',
				'x',
				[]
			],
			[
				'<r>..<X x=""/></r>',
				'X',
				'x',
				['']
			],
			[
				'<r>..<X a="x" x="z"/></r>',
				'X',
				'x',
				['z']
			],
			[
				'<r><X x="a&amp;b"/></r>',
				'X',
				'x',
				['a&b']
			],
			[
				'<r><X x="..&#128512;.."/></r>',
				'X',
				'x',
				['..😀..']
			],
			[
				'<r><FOO bar="123"/><FOO bar="456"/></r>',
				'FOO',
				'bar',
				['123', '456']
			],
			[
				'<r><FOO bar="123"/><FOOBAR bar="456"/></r>',
				'FOO',
				'bar',
				['123']
			],
			[
				'<r><FOO babar="123"/><FOO bar="456"/></r>',
				'FOO',
				'bar',
				['456']
			],
		];
	}

	/**
	* @testdox removeFormatting() tests
	* @dataProvider getRemoveFormattingTests
	*/
	public function testRemoveFormatting($original, $expected)
	{
		$this->assertSame($expected, Utils::removeFormatting($original));
	}

	public function getRemoveFormattingTests()
	{
		return [
			[
				'<t>Plain text</t>',
				'Plain text'
			],
			[
				'<t>&lt;Plain text&gt;</t>',
				'<Plain text>'
			],
			[
				"<t>a<br/>\nb</t>",
				"a\nb"
			],
			[
				'<r><B><s>[b]</s>Rich<e>[/b]</e></B> text <E>:)</E></r>',
				'Rich text :)'
			],
			[
				'<r><B><s>[b]</s>bold<e>[/b]</e></B> <B><s>[b]</s>text<e>[/b]</e></B></r>',
				'bold text'
			],
			[
				'<r><C><s>`</s>&#128512;<e>`</e></C></r>',
				'😀'
			],
		];
	}

	/**
	* @testdox removeTag() tests
	* @dataProvider getRemoveTagTests
	*/
	public function testRemoveTag($original, $args, $expected)
	{
		array_unshift($args, $original);
		$this->assertSame(
			$expected,
			call_user_func_array('s9e\\TextFormatter\\Utils::removeTag', $args)
		);
	}

	public function getRemoveTagTests()
	{
		return [
			[
				'<t>Plain text</t>',
				['X'],
				'<t>Plain text</t>'
			],
			[
				'<r><X><s>X</s>..<e>/X</e></X><Y>..</Y></r>',
				['X'],
				'<r><Y>..</Y></r>'
			],
			[
				'<r><X><s>X</s>..<X><s>X</s>..<e>/X</e></X>..<e>/X</e></X><Y>..</Y></r>',
				['X'],
				'<r><Y>..</Y></r>'
			],
			[
				'<r><X><s>X</s>..<X><s>X</s>..<e>/X</e></X>..<e>/X</e></X><Y>..</Y></r>',
				['X', 0],
				'<r><Y>..</Y></r>'
			],
			[
				'<r><X><s>X</s>..<X><s>X</s>..<e>/X</e></X>..<e>/X</e></X><Y>..</Y></r>',
				['X', 1],
				'<r><X><s>X</s>....<e>/X</e></X><Y>..</Y></r>'
			],
			[
				'<r><X><s>X</s>..<X><s>X</s>..<e>/X</e></X>..<e>/X</e></X><Y>..</Y></r>',
				['X', 2],
				'<r><X><s>X</s>..<X><s>X</s>..<e>/X</e></X>..<e>/X</e></X><Y>..</Y></r>'
			],
			[
				'<r><X><s>X</s>..<X><s>X</s>..<e>/X</e></X>..<e>/X</e></X><Y>..</Y></r>',
				['X', PHP_INT_MAX - 1],
				'<r><X><s>X</s>..<X><s>X</s>..<e>/X</e></X>..<e>/X</e></X><Y>..</Y></r>'
			],
			[
				'<r xmlns:foo="urn:foo"><X>..</X><foo:X>..</foo:X></r>',
				['X'],
				'<r xmlns:foo="urn:foo"><foo:X>..</foo:X></r>'
			],
			[
				'<r xmlns:foo="urn:foo"><X>..</X><foo:X>..</foo:X></r>',
				['foo:X'],
				'<r xmlns:foo="urn:foo"><X>..</X></r>'
			],
			[
				'<r><X/>&#128512;</r>',
				['X', 0],
				'<r>&#128512;</r>'
			],
		];
	}

	/**
	* @testdox replaceAttributes() tests
	* @dataProvider getReplaceAttributesTests
	*/
	public function testReplaceAttributes($original, $expected, $tagName, $callback)
	{
		$this->assertSame($expected, Utils::replaceAttributes($original, $tagName, $callback));
	}

	public function getReplaceAttributesTests()
	{
		return [
			[
				'<t>Plain text</t>',
				'<t>Plain text</t>',
				'X',
				function ()
				{
				}
			],
			[
				'<r><X/></r>',
				'<r><X attr="value"/></r>',
				'X',
				function ($attributes)
				{
					return ['attr' => 'value'];
				}
			],
			[
				'<r><X></X></r>',
				'<r><X attr="value"></X></r>',
				'X',
				function ($attributes)
				{
					return ['attr' => 'value'];
				}
			],
			[
				'<r><X/><X/><XX/></r>',
				'<r><X attr="value"/><X attr="value"/><XX/></r>',
				'X',
				function ($attributes)
				{
					return ['attr' => 'value'];
				}
			],
			[
				'<r><X/></r>',
				'<r><X bar="Bar" foo="Foo"/></r>',
				'X',
				function ($attributes)
				{
					return ['foo' => 'Foo', 'bar' => 'Bar'];
				}
			],
			[
				'<r><X/></r>',
				'<r><X attr="&amp;&quot;&lt;&gt;"/></r>',
				'X',
				function ($attributes)
				{
					return ['attr' => '&"<>'];
				}
			],
			[
				'<r><X a="A" b="B"/></r>',
				'<r><X a="s:1:&quot;A&quot;;" b="s:1:&quot;B&quot;;"/></r>',
				'X',
				function ($attributes)
				{
					foreach ($attributes as $attrName => $attrValue)
					{
						$attributes[$attrName] = serialize($attrValue);
					}
					return $attributes;
				}
			],
			[
				'<r><X a="&lt;"/></r>',
				'<r><X a="&lt;!"/></r>',
				'X',
				function ($attributes)
				{
					$attributes['a'] .= '!';
					return $attributes;
				}
			],
			[
				'<r><X emotes=":D"/></r>',
				'<r><X emotes="&#128513;"/></r>',
				'X',
				function ($attributes)
				{
					return ['emotes' => '😁'];
				}
			],
			[
				'<r><X foo=""/></r>',
				'<r><X foo="\'&quot;"/></r>',
				'X',
				function ($attributes)
				{
					return ['foo' => '\'"'];
				}
			],
			[
				'<r><X foo=""/></r>',
				'<r><X foo="a&#10;b"/></r>',
				'X',
				function ($attributes)
				{
					return ['foo' => "a\nb"];
				}
			],
			[
				'<r><X foo=""/></r>',
				'<r><X foo="a&#10;b"/></r>',
				'X',
				function ($attributes)
				{
					return ['foo' => "a\r\nb"];
				}
			],
		];
	}
}