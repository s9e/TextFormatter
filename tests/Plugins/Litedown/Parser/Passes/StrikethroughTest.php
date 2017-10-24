<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Strikethrough
*/
class StrikethroughTest extends AbstractTest
{
	public function getParsingTests()
	{
		return self::fixTests([
			[
				'.. ~~foo~~ ~~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo<e>~~</e></DEL> <DEL><s>~~</s>bar<e>~~</e></DEL> ..</p></r>'
			],
			[
				'.. ~~foo~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo~bar<e>~~</e></DEL> ..</p></r>'
			],
			[
				'.. ~~foo\\~~ ~~bar~~ ..',
				'<r><p>.. <DEL><s>~~</s>foo\\~~ <e>~~</e></DEL>bar~~ ..</p></r>'
			],
			[
				'.. ~~~~ ..',
				'<t><p>.. ~~~~ ..</p></t>'
			],
			[
				"~~foo\nbar~~",
				"<r><p><DEL><s>~~</s>foo\nbar<e>~~</e></DEL></p></r>"
			],
			[
				"~~foo\n\nbar~~",
				"<t><p>~~foo</p>\n\n<p>bar~~</p></t>"
			],
		]);
	}

	public function getRenderingTests()
	{
		return self::fixTests([
			[
				'~~x~~',
				'<p><del>x</del></p>'
			],
		]);
	}
}