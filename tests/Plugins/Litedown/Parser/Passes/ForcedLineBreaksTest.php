<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\ForcedLineBreaks
*/
class ForcedLineBreaksTest extends AbstractTest
{
	public function getParsingTests()
	{
		return self::fixTests([
			[
				'',
				'<t></t>',
			],
			[
				[
					'first line  ',
					'second line  ',
					'third line'
				],
				[
					'<t><p>first line  <br/>',
					'second line  <br/>',
					'third line</p></t>'
				],
			],
			[
				[
					'first line  ',
					'second line  '
				],
				[
					'<t><p>first line  <br/>',
					'second line</p>  </t>'
				],
			],
			[
				[
					'> first line  ',
					'> second line  ',
					'',
					'outside quote'
				],
				[
					'<r><QUOTE><i>&gt; </i><p>first line  <br/>',
					'<i>&gt; </i>second line</p>  </QUOTE>',
					'',
					'<p>outside quote</p></r>'
				],
			],
			[
				[
					'    first line  ',
					'    second line  ',
					'',
					'outside code'
				],
				[
					'<r><i>    </i><CODE>first line  ',
					'<i>    </i>second line  </CODE>',
					'',
					'<p>outside code</p></r>'
				],
			],
			[
				[
					'    first line  ',
					'',
					'outside code'
				],
				[
					'<r><i>    </i><CODE>first line  </CODE>',
					'',
					'<p>outside code</p></r>'
				],
			],
			[
				[
					' * first item  ',
					'   still the first item  ',
					' * second item',
					'',
					'outside list'
				],
				[
					'<r> <LIST><LI><s>* </s>first item  <br/>',
					'   still the first item  </LI>',
					' <LI><s>* </s>second item</LI></LIST>',
					'',
					'<p>outside list</p></r>'
				],
			],
			[
				[
					'foo  ',
					'---  ',
					'bar  '
				],
				[
					'<r><H2>foo<e>  ',
					'---  </e></H2>',
					'<p>bar</p>  </r>'
				]
			],
		]);
	}

	public function getRenderingTests()
	{
		return self::fixTests([
			[
				"x  \nx",
				"<p>x  <br>\nx</p>"
			],
		]);
	}
}