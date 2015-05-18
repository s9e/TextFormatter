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
				'<r xmlns:foo="urn:foo"><X>..</X><foo:X>..</foo:X></r>',
				['X'],
				'<r xmlns:foo="urn:foo"><foo:X>..</foo:X></r>'
			],
			[
				'<r xmlns:foo="urn:foo"><X>..</X><foo:X>..</foo:X></r>',
				['foo:X'],
				'<r xmlns:foo="urn:foo"><X>..</X></r>'
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
		];
	}
}