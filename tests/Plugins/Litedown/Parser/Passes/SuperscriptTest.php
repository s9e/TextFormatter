<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
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
				'.. foo^baar^baz 1^2 ..',
				'<r><p>.. foo<SUP><s>^</s>baar<SUP><s>^</s>baz</SUP></SUP> 1<SUP><s>^</s>2</SUP> ..</p></r>'
			],
			[
				'.. \\^_^ ..',
				'<t><p>.. \^_^ ..</p></t>'
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