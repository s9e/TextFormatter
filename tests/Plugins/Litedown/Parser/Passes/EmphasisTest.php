<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\ParsedText
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\AbstractPass
* @covers s9e\TextFormatter\Plugins\Litedown\Parser\Passes\Emphasis
*/
class EmphasisTest extends AbstractTest
{
	public function getParsingTests()
	{
		return self::fixTests([
			[
				'',
				'<t></t>',
			],
			[
				'xx ***x*****x** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx ***x****x* xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx ***x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'***strongem*strong***em*',
				'<r><p><STRONG><s>**</s><EM><s>*</s>strongem<e>*</e></EM>strong<e>**</e></STRONG><EM><s>*</s>em<e>*</e></EM></p></r>'
			],
			[
				'***emstrong**em***strong**',
				'<r><p><EM><s>*</s><STRONG><s>**</s>emstrong<e>**</e></STRONG>em<e>*</e></EM><STRONG><s>**</s>strong<e>**</e></STRONG></p></r>'
			],
			[
				'xx **x*****x*** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x****x** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x***x* xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG><EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx **x** xx',
				'<r><p>xx <STRONG><s>**</s>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx **x*x** xx',
				'<r><p>xx <STRONG><s>**</s>x*x<e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x*****x*** xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM>*<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x****x*** xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x**x* xx',
				'<r><p>xx <EM><s>*</s>x**x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx *x* xx',
				'<r><p>xx <EM><s>*</s>x<e>*</e></EM> xx</p></r>'
			],
			[
				'xx *x**x*x** xx',
				'<r><p>xx <EM><s>*</s>x<STRONG><s>**</s>x</STRONG><e>*</e></EM><STRONG>x<e>**</e></STRONG> xx</p></r>'
			],
			[
				"*foo\nbar*",
				"<r><p><EM><s>*</s>foo\nbar<e>*</e></EM></p></r>"
			],
			[
				"*foo\n\nbar*",
				"<t><p>*foo</p>\n\n<p>bar*</p></t>"
			],
			[
				"***foo*\n\nbar**",
				"<r><p>**<EM><s>*</s>foo<e>*</e></EM></p>\n\n<p>bar**</p></r>"
			],
			[
				"***foo**\n\nbar*",
				"<r><p>*<STRONG><s>**</s>foo<e>**</e></STRONG></p>\n\n<p>bar*</p></r>"
			],
			[
				'xx _x_ xx',
				'<r><p>xx <EM><s>_</s>x<e>_</e></EM> xx</p></r>'
			],
			[
				'xx __x__ xx',
				'<r><p>xx <STRONG><s>__</s>x<e>__</e></STRONG> xx</p></r>'
			],
			[
				'xx foo_bar_baz xx',
				'<t><p>xx foo_bar_baz xx</p></t>'
			],
			[
				'xx foo__bar__baz xx',
				'<r><p>xx foo<STRONG><s>__</s>bar<e>__</e></STRONG>baz xx</p></r>'
			],
			[
				'x _foo_',
				'<r><p>x <EM><s>_</s>foo<e>_</e></EM></p></r>'
			],
			[
				'_foo_ x',
				'<r><p><EM><s>_</s>foo<e>_</e></EM> x</p></r>'
			],
			[
				'_foo_',
				'<r><p><EM><s>_</s>foo<e>_</e></EM></p></r>'
			],
			[
				'xx ***x******x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG><STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx ***x*******x*** xx',
				'<r><p>xx <STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG>*<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *****x***** xx',
				'<r><p>xx **<STRONG><s>**</s><EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG>** xx</p></r>'
			],
			[
				'xx **x*x*** xx',
				'<r><p>xx <STRONG><s>**</s>x<EM><s>*</s>x<e>*</e></EM><e>**</e></STRONG> xx</p></r>'
			],
			[
				'xx *x**x*** xx',
				'<r><p>xx <EM><s>*</s>x<STRONG><s>**</s>x<e>**</e></STRONG><e>*</e></EM> xx</p></r>'
			],
			[
				'\\\\*foo*',
				'<r><p>\\\\<EM><s>*</s>foo<e>*</e></EM></p></r>'
			],
			[
				'*\\\\*foo*',
				'<r><p><EM><s>*</s>\\\\<e>*</e></EM>foo*</p></r>'
			],
			[
				'*x *x *x',
				'<t><p>*x *x *x</p></t>'
			],
			[
				'x* x* x*',
				'<t><p>x* x* x*</p></t>'
			],
			[
				'*x x* x*',
				'<r><p><EM><s>*</s>x x<e>*</e></EM> x*</p></r>'
			],
			[
				'*x *x x*',
				'<r><p>*x <EM><s>*</s>x x<e>*</e></EM></p></r>'
			],
			[
				'*x **x** x*',
				'<r><p><EM><s>*</s>x <STRONG><s>**</s>x<e>**</e></STRONG> x<e>*</e></EM></p></r>'
			],
			[
				'x * x * x',
				'<t><p>x * x * x</p></t>'
			],
			[
				'x * x*x * x',
				'<t><p>x * x*x * x</p></t>'
			],
			[
				"*x\nx*",
				"<r><p><EM><s>*</s>x\nx<e>*</e></EM></p></r>"
			],
			[
				"_\nx_",
				"<t><p>_\nx_</p></t>"
			],
		]);
	}

	public function getRenderingTests()
	{
		return self::fixTests([
			[
				'***x*** **x*x*** *x**x*** ***x**x* ***x*x**',
				'<p><strong><em>x</em></strong> <strong>x<em>x</em></strong> <em>x<strong>x</strong></em> <em><strong>x</strong>x</em> <strong><em>x</em>x</strong></p>'
			],
		]);
	}
}