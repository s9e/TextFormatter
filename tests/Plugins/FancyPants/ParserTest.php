<?php

namespace s9e\TextFormatter\Tests\Plugins\FancyPants;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\FancyPants\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\FancyPants\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return [
			[
				'...',
				'<r><FP char="…">...</FP></r>'
			],
			[
				'...',
				'<r><FP char="…">...</FP></r>',
				[],
				function ($configurator)
				{
					$configurator->FancyPants->disablePass('Quotes');
				}
			],
			[
				'...',
				'<t>...</t>',
				[],
				function ($configurator)
				{
					$configurator->FancyPants->disablePass('Punctuation');
				}
			],
			[
				'...',
				'<r><FOO char="…">...</FOO></r>',
				['tagName' => 'FOO']
			],
			[
				'...',
				'<r><FP bar="…">...</FP></r>',
				['attrName' => 'bar']
			],
			[
				"'Good morning, Frank,' greeted HAL.",
				'<r><FP char="‘">\'</FP>Good morning, Frank,<FP char="’">\'</FP> greeted HAL.</r>'
			],
			[
				"'Good morning, Frank,' greeted HAL.",
				"<t>'Good morning, Frank,' greeted HAL.</t>",
				[],
				function ($configurator)
				{
					$configurator->FancyPants->disablePass('Quotes');
				}
			],
			[
				"\"'Good morning, Frank,' greeted HAL.\" is how the book starts.",
				'<r><FP char="“">"</FP><FP char="‘">\'</FP>Good morning, Frank,<FP char="’">\'</FP> greeted HAL.<FP char="”">"</FP> is how the book starts.</r>'
			],
			[
				'"Good morning, Frank," greeted HAL.',
				'<r><FP char="“">"</FP>Good morning, Frank,<FP char="”">"</FP> greeted HAL.</r>'
			],
			[
				'\'"Good morning, Frank," greeted HAL.\' is how the book starts.',
				'<r><FP char="‘">\'</FP><FP char="“">"</FP>Good morning, Frank,<FP char="”">"</FP> greeted HAL.<FP char="’">\'</FP> is how the book starts.</r>'
			],
			[
				'Hello world...',
				'<r>Hello world<FP char="…">...</FP></r>'
			],
			[
				'foo--bar',
				'<r>foo<FP char="–">--</FP>bar</r>'
			],
			[
				'foo---bar',
				'<r>foo<FP char="—">---</FP>bar</r>'
			],
			[
				'(tm)',
				'<r><FP char="™">(tm)</FP></r>'
			],
			[
				'(TM)',
				'<r><FP char="™">(TM)</FP></r>'
			],
			[
				'(c)',
				'<r><FP char="©">(c)</FP></r>'
			],
			[
				'(c)',
				'<t>(c)</t>',
				[],
				function ($configurator)
				{
					$configurator->FancyPants->disablePass('Symbols');
				}
			],
			[
				'(C)',
				'<r><FP char="©">(C)</FP></r>'
			],
			[
				'(r)',
				'<r><FP char="®">(r)</FP></r>'
			],
			[
				'(R)',
				'<r><FP char="®">(R)</FP></r>'
			],
			[
				"'Twas the night. 'Twas the night before Christmas.",
				'<r><FP char="’">\'</FP>Twas the night. <FP char="’">\'</FP>Twas the night before Christmas.</r>'
			],
			[
				"Say. 'Twas the night before Christmas.",
				'<r>Say. <FP char="’">\'</FP>Twas the night before Christmas.</r>'
			],
			[
				"Occam's razor",
				'<r>Occam<FP char="’">\'</FP>s razor</r>'
			],
			[
				"Ridin' dirty",
				'<r>Ridin<FP char="’">\'</FP> dirty</r>'
			],
			[
				"Get rich or die tryin'",
				'<r>Get rich or die tryin<FP char="’">\'</FP></r>'
			],
			[
				"Get rich or die tryin', yo.",
				'<r>Get rich or die tryin<FP char="’">\'</FP>, yo.</r>'
			],
			[
				"'88 was the year. '88 was the year indeed.",
				'<r><FP char="’">\'</FP>88 was the year. <FP char="’">\'</FP>88 was the year indeed.</r>'
			],
			[
				"'88 bottles of beer on the wall'",
				'<r><FP char="‘">\'</FP>88 bottles of beer on the wall<FP char="’">\'</FP></r>'
			],
			[
				"1950's",
				'<r>1950<FP char="’">\'</FP>s</r>'
			],
			[
				"I am 7' tall",
				'<r>I am 7<FP char="′">\'</FP> tall</r>'
			],
			[
				'12" vinyl',
				'<r>12<FP char="″">"</FP> vinyl</r>'
			],
			[
				'3x3',
				'<r>3<FP char="×">x</FP>3</r>'
			],
			[
				'3 x 3',
				'<r>3 <FP char="×">x</FP> 3</r>'
			],
			[
				'3" x 3"',
				'<r>3<FP char="″">"</FP> <FP char="×">x</FP> 3<FP char="″">"</FP></r>'
			],
			[
				'3"x3"',
				'<r>3<FP char="″">"</FP><FP char="×">x</FP>3<FP char="″">"</FP></r>'
			],
			[
				"3' x 3'",
				'<r>3<FP char="′">\'</FP> <FP char="×">x</FP> 3<FP char="′">\'</FP></r>'
			],
			[
				"3'x3'",
				'<r>3<FP char="′">\'</FP><FP char="×">x</FP>3<FP char="′">\'</FP></r>'
			],
			[
				"O'Connor's pants",
				'<r>O<FP char="’">\'</FP>Connor<FP char="’">\'</FP>s pants</r>'
			],
			[
				'apples != oranges',
				'<r>apples <FP char="≠">!=</FP> oranges</r>'
			],
			[
				'apples =/= oranges',
				'<r>apples <FP char="≠">=/=</FP> oranges</r>'
			],
			[
				'apples != oranges',
				'<t>apples != oranges</t>',
				[],
				function ($configurator)
				{
					$configurator->FancyPants->disablePass('MathSymbols');
				}
			],
			[
				'<< Voulez-vous un sandwich, Henri ? >>',
				'<r><FP char="«">&lt;&lt;</FP> Voulez-vous un sandwich, Henri ? <FP char="»">&gt;&gt;</FP></r>'
			],
			[
				'<<A>> <<A >> << A>> << A >>',
				'<r><FP char="«">&lt;&lt;</FP>A<FP char="»">&gt;&gt;</FP> &lt;&lt;A &gt;&gt; &lt;&lt; A&gt;&gt; <FP char="«">&lt;&lt;</FP> A <FP char="»">&gt;&gt;</FP></r>'
			],
			[
				"<<A\n>>",
				"<t>&lt;&lt;A\n&gt;&gt;</t>"
			],
			[
				'<<A>>',
				'<t>&lt;&lt;A&gt;&gt;</t>',
				[],
				function ($configurator)
				{
					$configurator->FancyPants->disablePass('Guillemets');
				}
			],
			[
				'0/3 1/1 1/10 1/2 1/25 1/3 1/4 1/5 1/6 1/7 1/8 1/9 10/10 2/3 2/5 3/4 3/5 3/8 4/5 5/6 5/8 7/8',
				'<r><FP char="↉">0/3</FP> 1/1 <FP char="⅒">1/10</FP> <FP char="½">1/2</FP> 1/25 <FP char="⅓">1/3</FP> <FP char="¼">1/4</FP> <FP char="⅕">1/5</FP> <FP char="⅙">1/6</FP> <FP char="⅐">1/7</FP> <FP char="⅛">1/8</FP> <FP char="⅑">1/9</FP> 10/10 <FP char="⅔">2/3</FP> <FP char="⅖">2/5</FP> <FP char="¾">3/4</FP> <FP char="⅗">3/5</FP> <FP char="⅜">3/8</FP> <FP char="⅘">4/5</FP> <FP char="⅚">5/6</FP> <FP char="⅝">5/8</FP> <FP char="⅞">7/8</FP></r>'
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'...',
				'…'
			],
			[
				'...',
				'…',
				['tagName' => 'FOO']
			],
			[
				'...',
				'…',
				['attrName' => 'bar']
			],
			[
				"'Good morning, Frank,' greeted HAL.",
				'‘Good morning, Frank,’ greeted HAL.'
			],
			[
				"\"'Good morning, Frank,' greeted HAL.\" is how the book starts.",
				'“‘Good morning, Frank,’ greeted HAL.” is how the book starts.'
			],
			[
				'"Good morning, Frank," greeted HAL.',
				'“Good morning, Frank,” greeted HAL.'
			],
			[
				'\'"Good morning, Frank," greeted HAL.\' is how the book starts.',
				'‘“Good morning, Frank,” greeted HAL.’ is how the book starts.'
			],
			[
				'Hello world...',
				'Hello world…'
			],
			[
				'foo--bar',
				'foo–bar'
			],
			[
				'foo---bar',
				'foo—bar'
			],
			[
				'(tm)',
				'™'
			],
			[
				'(TM)',
				'™'
			],
			[
				'(c)',
				'©'
			],
			[
				'(C)',
				'©'
			],
			[
				'(r)',
				'®'
			],
			[
				'(R)',
				'®'
			],
			[
				"'Twas the night. 'Twas the night before Christmas.",
				'’Twas the night. ’Twas the night before Christmas.'
			],
			[
				"Say. 'Twas the night before Christmas.",
				'Say. ’Twas the night before Christmas.'
			],
			[
				"Occam's razor",
				'Occam’s razor'
			],
			[
				"Ridin' dirty",
				'Ridin’ dirty'
			],
			[
				"Get rich or die tryin'",
				'Get rich or die tryin’'
			],
			[
				"Get rich or die tryin', yo.",
				'Get rich or die tryin’, yo.'
			],
			[
				"'88 was the year. '88 was the year indeed.",
				'’88 was the year. ’88 was the year indeed.'
			],
			[
				"'88 bottles of beer on the wall'",
				'‘88 bottles of beer on the wall’'
			],
			[
				"1950's",
				"1950’s"
			],
			[
				"I am 7' tall",
				"I am 7′ tall"
			],
			[
				'12" vinyl',
				'12″ vinyl'
			],
			[
				'3x3',
				'3×3'
			],
			[
				'3 x 3',
				'3 × 3'
			],
			[
				'3" x 3"',
				'3″ × 3″'
			],
			[
				"O'Connor's pants",
				'O’Connor’s pants'
			],
			[
				'1/4 x 2/3 = 1/6',
				'¼ × ⅔ = ⅙'
			],
		];
	}
}