<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractScript
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Subscript
*/
class SubscriptTest extends AbstractTest
{
	public function getParsingTests()
	{
		return self::fixTests([
			[
				'',
				'<t></t>',
			],
			[
				'x~2 y~2',
				'<r><p>x<SUB><s>~</s>2</SUB> y<SUB><s>~</s>2</SUB></p></r>'
			],
			[
				'H~2~O',
				'<r><p>H<SUB><s>~</s>2<e>~</e></SUB>O</p></r>'
			],
			[
				'x~(n - 1)',
				'<r><p>x<SUB><s>~(</s>n - 1<e>)</e></SUB></p></r>'
			],
			[
				'x~(n~(n - 1))',
				'<r><p>x<SUB><s>~(</s>n<SUB><s>~(</s>n - 1<e>)</e></SUB><e>)</e></SUB></p></r>'
			],
			[
				'x~(n~(n~2))',
				'<r><p>x<SUB><s>~(</s>n<SUB><s>~(</s>n<SUB><s>~</s>2</SUB><e>)</e></SUB><e>)</e></SUB></p></r>'
			],
			[
				':~(',
				'<t><p>:~(</p></t>'
			],
			[
				[
					':~(',
					'',
					')'
				],
				[
					'<t><p>:~(</p>',
					'',
					'<p>)</p></t>'
				]
			],
			[
				[
					'x~2',
					'',
					'x~2'
				],
				[
					'<r><p>x<SUB><s>~</s>2</SUB></p>',
					'',
					'<p>x<SUB><s>~</s>2</SUB></p></r>'
				]
			],
			[
				'~~H~2~O~~',
				'<r><p><DEL><s>~~</s>H<SUB><s>~</s>2<e>~</e></SUB>O<e>~~</e></DEL></p></r>'
			],
			[
				'~_^',
				'<t><p>~_^</p></t>'
			],
			[
				'~(_)',
				'<r><p><SUB><s>~(</s>_<e>)</e></SUB></p></r>'
			],
			[
				'~(\\(\\\\\\))',
				'<r><p><SUB><s>~(</s>\\(\\\\\\)<e>)</e></SUB></p></r>'
			],
		]);
	}

	public function getRenderingTests()
	{
		return self::fixTests([
			[
				'x~1',
				'<p>x<sub>1</sub></p>'
			]
		]);
	}
}