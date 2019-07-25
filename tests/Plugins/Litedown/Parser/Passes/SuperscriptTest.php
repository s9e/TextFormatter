<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractScript
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Superscript
*/
class SuperscriptTest extends AbstractTest
{
	public function getParsingTests()
	{
		return self::fixTests([
			[
				'',
				'<t></t>',
			],
			[
				'x^2 y^2',
				'<r><p>x<SUP><s>^</s>2</SUP> y<SUP><s>^</s>2</SUP></p></r>'
			],
			[
				'4^th^ of July',
				'<r><p>4<SUP><s>^</s>th<e>^</e></SUP> of July</p></r>'
			],
			[
				'x^(n - 1)',
				'<r><p>x<SUP><s>^(</s>n - 1<e>)</e></SUP></p></r>'
			],
			[
				'x^(n^(n - 1))',
				'<r><p>x<SUP><s>^(</s>n<SUP><s>^(</s>n - 1<e>)</e></SUP><e>)</e></SUP></p></r>'
			],
			[
				'x^(n^(n^2))',
				'<r><p>x<SUP><s>^(</s>n<SUP><s>^(</s>n<SUP><s>^</s>2</SUP><e>)</e></SUP><e>)</e></SUP></p></r>'
			],
			[
				':^(',
				'<t><p>:^(</p></t>'
			],
			[
				[
					':^(',
					'',
					')'
				],
				[
					'<t><p>:^(</p>',
					'',
					'<p>)</p></t>'
				]
			],
			[
				[
					'x^2',
					'',
					'x^2'
				],
				[
					'<r><p>x<SUP><s>^</s>2</SUP></p>',
					'',
					'<p>x<SUP><s>^</s>2</SUP></p></r>'
				]
			],
			[
				'^_^',
				'<t><p>^_^</p></t>'
			],
			[
				'^(_)',
				'<r><p><SUP><s>^(</s>_<e>)</e></SUP></p></r>'
			],
			[
				'^(\\(\\\\\\))',
				'<r><p><SUP><s>^(</s>\\(\\\\\\)<e>)</e></SUP></p></r>'
			],
		]);
	}

	public function getRenderingTests()
	{
		return self::fixTests([
			[
				'x^1',
				'<p>x<sup>1</sup></p>'
			]
		]);
	}
}