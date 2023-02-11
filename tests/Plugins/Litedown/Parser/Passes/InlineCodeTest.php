<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\InlineCode
*/
class InlineCodeTest extends AbstractTestClass
{
	public static function getParsingTests()
	{
		return self::fixTests([
			[
				'',
				'<t></t>',
			],
			[
				'.. `foo` `bar` ..',
				'<r><p>.. <C><s>`</s>foo<e>`</e></C> <C><s>`</s>bar<e>`</e></C> ..</p></r>'
			],
			[
				'.. `foo `` bar` ..',
				'<r><p>.. <C><s>`</s>foo `` bar<e>`</e></C> ..</p></r>'
			],
			[
				'.. `foo ``` bar` ..',
				'<r><p>.. <C><s>`</s>foo ``` bar<e>`</e></C> ..</p></r>'
			],
			[
				'.. ``foo`` ``bar`` ..',
				'<r><p>.. <C><s>``</s>foo<e>``</e></C> <C><s>``</s>bar<e>``</e></C> ..</p></r>'
			],
			[
				'.. ``foo `bar` baz`` ..',
				'<r><p>.. <C><s>``</s>foo `bar` baz<e>``</e></C> ..</p></r>'
			],
			[
				'`\\`',
				'<r><p><C><s>`</s>\\<e>`</e></C></p></r>'
			],
			[
				'\\``\\`',
				'<r><p>\\`<C><s>`</s>\\<e>`</e></C></p></r>'
			],
			[
				'.. ` x\\`` ` ..',
				'<r><p>.. <C><s>` </s>x\``<e> `</e></C> ..</p></r>'
			],
			[
				'`x` \\` `\\`',
				'<r><p><C><s>`</s>x<e>`</e></C> \\` <C><s>`</s>\\<e>`</e></C></p></r>'
			],
			[
				'.. `[foo](http://example.org)` ..',
				'<r><p>.. <C><s>`</s>[foo](http://example.org)<e>`</e></C> ..</p></r>'
			],
			[
				'.. `![foo](http://example.org)` ..',
				'<r><p>.. <C><s>`</s>![foo](http://example.org)<e>`</e></C> ..</p></r>'
			],
			[
				'.. `x` ..',
				'<r><p>.. <C><s>`</s>x<e>`</e></C> ..</p></r>'
			],
			[
				'.. ``x`` ..',
				'<r><p>.. <C><s>``</s>x<e>``</e></C> ..</p></r>'
			],
			[
				'.. ```x``` ..',
				'<r><p>.. <C><s>```</s>x<e>```</e></C> ..</p></r>'
			],
			[
				"`foo\nbar`",
				"<r><p><C><s>`</s>foo\nbar<e>`</e></C></p></r>"
			],
			[
				"`foo\n\nbar`",
				"<t><p>`foo</p>\n\n<p>bar`</p></t>"
			],
			[
				'```code```',
				'<r><p><C><s>```</s>code<e>```</e></C></p></r>'
			],
			[
				'``` code ```',
				'<r><p><C><s>``` </s>code<e> ```</e></C></p></r>'
			],
			[
				'``` co````de ```',
				'<r><p><C><s>``` </s>co````de<e> ```</e></C></p></r>'
			],
			[
				'``` ```',
				'<r><p><C><s>``` </s><e>```</e></C></p></r>'
			],
			[
				'``` `` ```',
				'<r><p><C><s>``` </s>``<e> ```</e></C></p></r>'
			],
			[
				'` `` `',
				'<r><p><C><s>` </s>``<e> `</e></C></p></r>'
			],
			[
				'``` x ``',
				'<t><p>``` x ``</p></t>'
			],
			[
				'x ``` x ``',
				'<t><p>x ``` x ``</p></t>'
			],
		]);
	}

	public static function getRenderingTests()
	{
		return self::fixTests([
			[
				'x `x` x',
				'<p>x <code>x</code> x</p>'
			],
		]);
	}
}