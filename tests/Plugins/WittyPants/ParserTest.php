<?php

namespace s9e\TextFormatter\Tests\Plugins\WittyPants;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\WittyPants\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\WittyPants\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				'...',
				'<rt><WP char="…">...</WP></rt>'
			),
			array(
				'...',
				'<rt><FOO char="…">...</FOO></rt>',
				array('tagName' => 'FOO')
			),
			array(
				'...',
				'<rt><WP bar="…">...</WP></rt>',
				array('attrName' => 'bar')
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'...',
				'…'
			),
			array(
				'...',
				'…',
				array('tagName' => 'FOO')
			),
			array(
				'...',
				'…',
				array('attrName' => 'bar')
			),
			array(
				"'Good morning, Frank,' greeted HAL.",
				'‘Good morning, Frank,’ greeted HAL.'
			),
			array(
				"\"'Good morning, Frank,' greeted HAL.\" is how the book starts.",
				'“‘Good morning, Frank,’ greeted HAL.” is how the book starts.'
			),
			array(
				'"Good morning, Frank," greeted HAL.',
				'“Good morning, Frank,” greeted HAL.'
			),
			array(
				'\'"Good morning, Frank," greeted HAL.\' is how the book starts.',
				'‘“Good morning, Frank,” greeted HAL.’ is how the book starts.'
			),
			array(
				'Hello world...',
				'Hello world…'
			),
			array(
				'foo--bar',
				'foo–bar'
			),
			array(
				'foo---bar',
				'foo—bar'
			),
			array(
				'(tm)',
				'™'
			),
			array(
				'(TM)',
				'™'
			),
			array(
				'(c)',
				'©'
			),
			array(
				'(C)',
				'©'
			),
			array(
				'(r)',
				'®'
			),
			array(
				'(R)',
				'®'
			),
			array(
				"'Twas the night. 'Twas the night before Christmas.",
				'’Twas the night. ’Twas the night before Christmas.'
			),
			array(
				"Say. 'Twas the night before Christmas.",
				'Say. ’Twas the night before Christmas.'
			),
			array(
				"Occam's razor",
				'Occam’s razor'
			),
			array(
				"Ridin' dirty",
				'Ridin’ dirty'
			),
			array(
				"Get rich or die tryin'",
				'Get rich or die tryin’'
			),
			array(
				"Get rich or die tryin', yo.",
				'Get rich or die tryin’, yo.'
			),
			array(
				"'88 was the year. '88 was the year indeed.",
				'’88 was the year. ’88 was the year indeed.'
			),
			array(
				"'88 bottles of beer on the wall'",
				'‘88 bottles of beer on the wall’'
			),
			array(
				"1950's",
				"1950’s"
			),
			array(
				"I am 7' tall",
				"I am 7′ tall"
			),
			array(
				'12" vinyl',
				'12″ vinyl'
			),
			array(
				'3x3',
				'3×3'
			),
			array(
				'3 x 3',
				'3 × 3'
			),
			array(
				'3" x 3"',
				'3″ × 3″'
			),
			array(
				"O'Connor's pants",
				'O’Connor’s pants'
			)
		);
	}
}