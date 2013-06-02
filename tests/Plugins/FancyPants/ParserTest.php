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
				'<rt><WP char="…">...</WP></rt>'
			],
			[
				'...',
				'<rt><FOO char="…">...</FOO></rt>',
				['tagName' => 'FOO']
			],
			[
				'...',
				'<rt><WP bar="…">...</WP></rt>',
				['attrName' => 'bar']
			],
			[
				"'Good morning, Frank,' greeted HAL.",
				'<rt><WP char="‘">\'</WP>Good morning, Frank,<WP char="’">\'</WP> greeted HAL.</rt>'
			],
			[
				"\"'Good morning, Frank,' greeted HAL.\" is how the book starts.",
				'<rt><WP char="“">"</WP><WP char="‘">\'</WP>Good morning, Frank,<WP char="’">\'</WP> greeted HAL.<WP char="”">"</WP> is how the book starts.</rt>'
			],
			[
				'"Good morning, Frank," greeted HAL.',
				'<rt><WP char="“">"</WP>Good morning, Frank,<WP char="”">"</WP> greeted HAL.</rt>'
			],
			[
				'\'"Good morning, Frank," greeted HAL.\' is how the book starts.',
				'<rt><WP char="‘">\'</WP><WP char="“">"</WP>Good morning, Frank,<WP char="”">"</WP> greeted HAL.<WP char="’">\'</WP> is how the book starts.</rt>'
			],
			[
				'Hello world...',
				'<rt>Hello world<WP char="…">...</WP></rt>'
			],
			[
				'foo--bar',
				'<rt>foo<WP char="–">--</WP>bar</rt>'
			],
			[
				'foo---bar',
				'<rt>foo<WP char="—">---</WP>bar</rt>'
			],
			[
				'(tm)',
				'<rt><WP char="™">(tm)</WP></rt>'
			],
			[
				'(TM)',
				'<rt><WP char="™">(TM)</WP></rt>'
			],
			[
				'(c)',
				'<rt><WP char="©">(c)</WP></rt>'
			],
			[
				'(C)',
				'<rt><WP char="©">(C)</WP></rt>'
			],
			[
				'(r)',
				'<rt><WP char="®">(r)</WP></rt>'
			],
			[
				'(R)',
				'<rt><WP char="®">(R)</WP></rt>'
			],
			[
				"'Twas the night. 'Twas the night before Christmas.",
				'<rt><WP char="’">\'</WP>Twas the night. <WP char="’">\'</WP>Twas the night before Christmas.</rt>'
			],
			[
				"Say. 'Twas the night before Christmas.",
				'<rt>Say. <WP char="’">\'</WP>Twas the night before Christmas.</rt>'
			],
			[
				"Occam's razor",
				'<rt>Occam<WP char="’">\'</WP>s razor</rt>'
			],
			[
				"Ridin' dirty",
				'<rt>Ridin<WP char="’">\'</WP> dirty</rt>'
			],
			[
				"Get rich or die tryin'",
				'<rt>Get rich or die tryin<WP char="’">\'</WP></rt>'
			],
			[
				"Get rich or die tryin', yo.",
				'<rt>Get rich or die tryin<WP char="’">\'</WP>, yo.</rt>'
			],
			[
				"'88 was the year. '88 was the year indeed.",
				'<rt><WP char="’">\'</WP>88 was the year. <WP char="’">\'</WP>88 was the year indeed.</rt>'
			],
			[
				"'88 bottles of beer on the wall'",
				'<rt><WP char="‘">\'</WP>88 bottles of beer on the wall<WP char="’">\'</WP></rt>'
			],
			[
				"1950's",
				'<rt>1950<WP char="’">\'</WP>s</rt>'
			],
			[
				"I am 7' tall",
				'<rt>I am 7<WP char="′">\'</WP> tall</rt>'
			],
			[
				'12" vinyl',
				'<rt>12<WP char="″">"</WP> vinyl</rt>'
			],
			[
				'3x3',
				'<rt>3<WP char="×">x</WP>3</rt>'
			],
			[
				'3 x 3',
				'<rt>3 <WP char="×">x</WP> 3</rt>'
			],
			[
				'3" x 3"',
				'<rt>3<WP char="″">"</WP> <WP char="×">x</WP> 3<WP char="″">"</WP></rt>'
			],
			[
				"O'Connor's pants",
				'<rt>O<WP char="’">\'</WP>Connor<WP char="’">\'</WP>s pants</rt>'
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