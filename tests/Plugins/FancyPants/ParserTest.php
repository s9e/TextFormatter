<?php

namespace s9e\TextFormatter\Tests\Plugins\FancyPants;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\FancyPants\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\FancyPants\Parser
*/
class ParserTest extends Test
{
	/**
	* @testdox Parsing tests
	* @dataProvider getParsingTests
	*/
	public function testParsing($original, $expected, array $pluginOptions = array(), $setup = null, $expectedJS = null, $assertMethod = 'assertSame')
	{
		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$configurator = new Configurator;
		$plugin = $configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($configurator, $plugin);
		}

		$this->$assertMethod($expected, $configurator->getParser()->parse($original));
	}

	/**
	* @group needs-js
	* @testdox Parsing tests (JavaScript)
	* @dataProvider getParsingTests
	* @requires extension json
	* @covers s9e\TextFormatter\Configurator\JavaScript
	*/
	public function testJavaScriptParsing($original, $expected, array $pluginOptions = array(), $setup = null, $expectedJS = null, $assertMethod = 'assertSame')
	{
		if (isset($expectedJS))
		{
			$expected = $expectedJS;
		}

		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$plugin = $this->configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($this->configurator, $plugin);
		}

		$this->assertJSParsing($original, $expected);
	}

	/**
	* @requires extension xsl
	* @testdox Parsing+rendering tests
	* @dataProvider getRenderingTests
	*/
	public function testRendering($original, $expected, array $pluginOptions = array(), $setup = null, $assertMethod = 'assertSame')
	{
		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$plugin = $this->configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($this->configurator, $plugin);
		}

		extract($this->configurator->finalize());

		$this->$assertMethod($expected, $renderer->render($parser->parse($original)));
	}

	public function getParsingTests()
	{
		return array(
			array(
				'...',
				'<r><FP char="…">...</FP></r>'
			),
			array(
				'...',
				'<r><FOO char="…">...</FOO></r>',
				array('tagName' => 'FOO')
			),
			array(
				'...',
				'<r><FP bar="…">...</FP></r>',
				array('attrName' => 'bar')
			),
			array(
				"'Good morning, Frank,' greeted HAL.",
				'<r><FP char="‘">\'</FP>Good morning, Frank,<FP char="’">\'</FP> greeted HAL.</r>'
			),
			array(
				"\"'Good morning, Frank,' greeted HAL.\" is how the book starts.",
				'<r><FP char="“">"</FP><FP char="‘">\'</FP>Good morning, Frank,<FP char="’">\'</FP> greeted HAL.<FP char="”">"</FP> is how the book starts.</r>'
			),
			array(
				'"Good morning, Frank," greeted HAL.',
				'<r><FP char="“">"</FP>Good morning, Frank,<FP char="”">"</FP> greeted HAL.</r>'
			),
			array(
				'\'"Good morning, Frank," greeted HAL.\' is how the book starts.',
				'<r><FP char="‘">\'</FP><FP char="“">"</FP>Good morning, Frank,<FP char="”">"</FP> greeted HAL.<FP char="’">\'</FP> is how the book starts.</r>'
			),
			array(
				'Hello world...',
				'<r>Hello world<FP char="…">...</FP></r>'
			),
			array(
				'foo--bar',
				'<r>foo<FP char="–">--</FP>bar</r>'
			),
			array(
				'foo---bar',
				'<r>foo<FP char="—">---</FP>bar</r>'
			),
			array(
				'(tm)',
				'<r><FP char="™">(tm)</FP></r>'
			),
			array(
				'(TM)',
				'<r><FP char="™">(TM)</FP></r>'
			),
			array(
				'(c)',
				'<r><FP char="©">(c)</FP></r>'
			),
			array(
				'(C)',
				'<r><FP char="©">(C)</FP></r>'
			),
			array(
				'(r)',
				'<r><FP char="®">(r)</FP></r>'
			),
			array(
				'(R)',
				'<r><FP char="®">(R)</FP></r>'
			),
			array(
				"'Twas the night. 'Twas the night before Christmas.",
				'<r><FP char="’">\'</FP>Twas the night. <FP char="’">\'</FP>Twas the night before Christmas.</r>'
			),
			array(
				"Say. 'Twas the night before Christmas.",
				'<r>Say. <FP char="’">\'</FP>Twas the night before Christmas.</r>'
			),
			array(
				"Occam's razor",
				'<r>Occam<FP char="’">\'</FP>s razor</r>'
			),
			array(
				"Ridin' dirty",
				'<r>Ridin<FP char="’">\'</FP> dirty</r>'
			),
			array(
				"Get rich or die tryin'",
				'<r>Get rich or die tryin<FP char="’">\'</FP></r>'
			),
			array(
				"Get rich or die tryin', yo.",
				'<r>Get rich or die tryin<FP char="’">\'</FP>, yo.</r>'
			),
			array(
				"'88 was the year. '88 was the year indeed.",
				'<r><FP char="’">\'</FP>88 was the year. <FP char="’">\'</FP>88 was the year indeed.</r>'
			),
			array(
				"'88 bottles of beer on the wall'",
				'<r><FP char="‘">\'</FP>88 bottles of beer on the wall<FP char="’">\'</FP></r>'
			),
			array(
				"1950's",
				'<r>1950<FP char="’">\'</FP>s</r>'
			),
			array(
				"I am 7' tall",
				'<r>I am 7<FP char="′">\'</FP> tall</r>'
			),
			array(
				'12" vinyl',
				'<r>12<FP char="″">"</FP> vinyl</r>'
			),
			array(
				'3x3',
				'<r>3<FP char="×">x</FP>3</r>'
			),
			array(
				'3 x 3',
				'<r>3 <FP char="×">x</FP> 3</r>'
			),
			array(
				'3" x 3"',
				'<r>3<FP char="″">"</FP> <FP char="×">x</FP> 3<FP char="″">"</FP></r>'
			),
			array(
				"O'Connor's pants",
				'<r>O<FP char="’">\'</FP>Connor<FP char="’">\'</FP>s pants</r>'
			)
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