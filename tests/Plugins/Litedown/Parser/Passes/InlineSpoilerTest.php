<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractInlineMarkup
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\InlineSpoiler
*/
class InlineSpoilerTest extends AbstractTestClass
{
	public static function getParsingTests()
	{
		return self::fixTests([
			[
				'',
				'<t></t>',
			],
			[
				// https://www.reddit.com/wiki/commenting#wiki_posting
				'.. >! spoiler !< ..',
				'<r><p>.. <ISPOILER><s>&gt;!</s> spoiler <e>!&lt;</e></ISPOILER> ..</p></r>'
			],
			[
				'.. >! 1 !< .. >! 2 !< ..',
				'<r><p>.. <ISPOILER><s>&gt;!</s> 1 <e>!&lt;</e></ISPOILER> .. <ISPOILER><s>&gt;!</s> 2 <e>!&lt;</e></ISPOILER> ..</p></r>'
			],
			[
				'>! spoiler !<',
				'<r><p><ISPOILER><s>&gt;!</s> spoiler <e>!&lt;</e></ISPOILER></p></r>'
			],
			[
				// https://support.discordapp.com/hc/en-us/articles/360022320632-Spoiler-Tags-
				'||spoiler||',
				'<r><p><ISPOILER><s>||</s>spoiler<e>||</e></ISPOILER></p></r>'
			],
			[
				[
					'There is a >!spoiler',
					'here!<',
				],
				[
					'<r><p>There is a <ISPOILER><s>&gt;!</s>spoiler',
					'here<e>!&lt;</e></ISPOILER></p></r>'
				]
			],
			[
				[
					'but >! no spoiler',
					'',
					'there !<',
				],
				[
					'<t><p>but &gt;! no spoiler</p>',
					'',
					'<p>there !&lt;</p></t>',
				]
			],
		]);
	}

	public static function getRenderingTests()
	{
		return self::fixTests([
			[
				'.. >! spoiler !< ..',
				'<p>.. <span class="spoiler" onclick="this.removeAttribute(\'style\')" style="background:#444;color:transparent"> spoiler </span> ..</p>'
			],
		]);
	}
}