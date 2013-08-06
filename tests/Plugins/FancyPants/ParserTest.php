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
				'<rt><FP char="…">...</FP></rt>'
			],
			[
				'...',
				'<rt><FOO char="…">...</FOO></rt>',
				['tagName' => 'FOO']
			],
			[
				'...',
				'<rt><FP bar="…">...</FP></rt>',
				['attrName' => 'bar']
			],
			[
				"'Good morning, Frank,' greeted HAL.",
				'<rt><FP char="‘">\'</FP>Good morning, Frank,<FP char="’">\'</FP> greeted HAL.</rt>'
			],
			[
				"\"'Good morning, Frank,' greeted HAL.\" is how the book starts.",
				'<rt><FP char="“">"</FP><FP char="‘">\'</FP>Good morning, Frank,<FP char="’">\'</FP> greeted HAL.<FP char="”">"</FP> is how the book starts.</rt>'
			],
			[
				'"Good morning, Frank," greeted HAL.',
				'<rt><FP char="“">"</FP>Good morning, Frank,<FP char="”">"</FP> greeted HAL.</rt>'
			],
			[
				'\'"Good morning, Frank," greeted HAL.\' is how the book starts.',
				'<rt><FP char="‘">\'</FP><FP char="“">"</FP>Good morning, Frank,<FP char="”">"</FP> greeted HAL.<FP char="’">\'</FP> is how the book starts.</rt>'
			],
			[
				'Hello world...',
				'<rt>Hello world<FP char="…">...</FP></rt>'
			],
			[
				'foo--bar',
				'<rt>foo<FP char="–">--</FP>bar</rt>'
			],
			[
				'foo---bar',
				'<rt>foo<FP char="—">---</FP>bar</rt>'
			],
			[
				'(tm)',
				'<rt><FP char="™">(tm)</FP></rt>'
			],
			[
				'(TM)',
				'<rt><FP char="™">(TM)</FP></rt>'
			],
			[
				'(c)',
				'<rt><FP char="©">(c)</FP></rt>'
			],
			[
				'(C)',
				'<rt><FP char="©">(C)</FP></rt>'
			],
			[
				'(r)',
				'<rt><FP char="®">(r)</FP></rt>'
			],
			[
				'(R)',
				'<rt><FP char="®">(R)</FP></rt>'
			],
			[
				"'Twas the night. 'Twas the night before Christmas.",
				'<rt><FP char="’">\'</FP>Twas the night. <FP char="’">\'</FP>Twas the night before Christmas.</rt>'
			],
			[
				"Say. 'Twas the night before Christmas.",
				'<rt>Say. <FP char="’">\'</FP>Twas the night before Christmas.</rt>'
			],
			[
				"Occam's razor",
				'<rt>Occam<FP char="’">\'</FP>s razor</rt>'
			],
			[
				"Ridin' dirty",
				'<rt>Ridin<FP char="’">\'</FP> dirty</rt>'
			],
			[
				"Get rich or die tryin'",
				'<rt>Get rich or die tryin<FP char="’">\'</FP></rt>'
			],
			[
				"Get rich or die tryin', yo.",
				'<rt>Get rich or die tryin<FP char="’">\'</FP>, yo.</rt>'
			],
			[
				"'88 was the year. '88 was the year indeed.",
				'<rt><FP char="’">\'</FP>88 was the year. <FP char="’">\'</FP>88 was the year indeed.</rt>'
			],
			[
				"'88 bottles of beer on the wall'",
				'<rt><FP char="‘">\'</FP>88 bottles of beer on the wall<FP char="’">\'</FP></rt>'
			],
			[
				"1950's",
				'<rt>1950<FP char="’">\'</FP>s</rt>'
			],
			[
				"I am 7' tall",
				'<rt>I am 7<FP char="′">\'</FP> tall</rt>'
			],
			[
				'12" vinyl',
				'<rt>12<FP char="″">"</FP> vinyl</rt>'
			],
			[
				'3x3',
				'<rt>3<FP char="×">x</FP>3</rt>'
			],
			[
				'3 x 3',
				'<rt>3 <FP char="×">x</FP> 3</rt>'
			],
			[
				'3" x 3"',
				'<rt>3<FP char="″">"</FP> <FP char="×">x</FP> 3<FP char="″">"</FP></rt>'
			],
			[
				"O'Connor's pants",
				'<rt>O<FP char="’">\'</FP>Connor<FP char="’">\'</FP>s pants</rt>'
			]
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
			]
		];
	}
}