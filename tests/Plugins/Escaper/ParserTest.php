<?php

namespace s9e\TextFormatter\Tests\Plugins\Escaper;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Escaper\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Escaper\Parser
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
				'\\[',
				'<r><ESC><s>\\</s>[</ESC></r>'
			),
			array(
				'\\[',
				'<r><FOO><s>\\</s>[</FOO></r>',
				array('tagName' => 'FOO')
			),
			array(
				"a\\\nb",
				"<r>a<ESC><s>\\</s>\n</ESC>b</r>",
				array(),
				function ($configurator, $plugin)
				{
					$plugin->escapeAll();
				}
			),
			array(
				'a\\♥b',
				'<r>a<ESC><s>\\</s>♥</ESC>b</r>',
				array(),
				function ($configurator, $plugin)
				{
					$plugin->escapeAll();
				}
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'\\[',
				'['
			),
			array(
				'\\[',
				'[',
				array('tagName' => 'FOO')
			),
			array(
				"a\\\nb",
				"a\nb",
				array(),
				function ($configurator, $plugin)
				{
					$plugin->escapeAll();
				}
			),
			array(
				'a\\♥b',
				'a♥b',
				array(),
				function ($configurator, $plugin)
				{
					$plugin->escapeAll();
				}
			),
		);
	}
}