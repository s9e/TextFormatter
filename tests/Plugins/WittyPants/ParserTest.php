<?php

namespace s9e\TextFormatter\Tests\Plugins\WittyPants;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\WittyPants\Parser;

/**
* @covers s9e\TextFormatter\Plugins\WittyPants\Parser
*/
class ParserTest extends Test
{
	/**
	* @testdox Parser works
	* @dataProvider getData
	*/
	public function test($original, $expected, $setup = null)
	{
		$this->configurator->plugins->load('WittyPants');

		$parser   = $this->configurator->getParser();
		$renderer = $this->configurator->getRenderer();

		$this->assertSame($expected, $renderer->render($parser->parse($original)));
	}

	public function getData()
	{
		return array(
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