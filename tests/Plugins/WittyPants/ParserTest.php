<?php

namespace s9e\TextFormatter\Tests\Plugins\WittyPants;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\WittyPants\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\WittyPants\Parser
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