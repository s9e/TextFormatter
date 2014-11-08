<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoji;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Emoji\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoji\Parser
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
				'☺',
				'<r><E1 seq="263A">☺</E1></r>'
			),
			array(
				'☺',
				'<r><EMOJI seq="263A">☺</EMOJI></r>',
				array('tagName' => 'EMOJI')
			),
			array(
				'☺',
				'<r><E1 cp="263A">☺</E1></r>',
				array('attrName' => 'cp')
			),
			array(
				':bouquet:',
				'<r><E1 seq="1F490">:bouquet:</E1></r>'
			),
			array(
				':xyz:',
				'<t>:xyz:</t>'
			),
			array(
				':xyz:bouquet:',
				'<r>:xyz<E1 seq="1F490">:bouquet:</E1></r>'
			),
			array(
				'☺☺',
				'<r><E1 seq="263A">☺</E1><E1 seq="263A">☺</E1></r>'
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'☺',
				'<img alt="☺" class="emojione" src="//cdn.jsdelivr.net/emojione/assets/png/263A.png">'
			),
			array(
				'☺',
				'<img alt="☺" class="emojione" src="//cdn.jsdelivr.net/emojione/assets/png/263A.png">',
				array('tagName' => 'EMOTE')
			),
			array(
				file_get_contents(__DIR__ . '/all.txt'),
				file_get_contents(__DIR__ . '/all.html'),
				array(),
				function ($configurator, $plugin)
				{
					$plugin->setRegexpLimit(10000);
					$plugin->getTag()->tagLimit = 10000;
				}
			),
		);
	}
}