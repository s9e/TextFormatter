<?php

namespace s9e\TextFormatter\Tests\Plugins\Autoemail;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Autoemail\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Autoemail\Parser
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
				'Hit me at example@example.com',
				'<r>Hit me at <EMAIL email="example@example.com">example@example.com</EMAIL></r>'
			),
			array(
				'Hit me at example@example.com.',
				'<r>Hit me at <EMAIL email="example@example.com">example@example.com</EMAIL>.</r>'
			),
			array(
				'Hit me at example@example.com',
				'<r>Hit me at <FOO email="example@example.com">example@example.com</FOO></r>',
				array('tagName' => 'FOO')
			),
			array(
				'Hit me at example@example.com',
				'<r>Hit me at <EMAIL bar="example@example.com">example@example.com</EMAIL></r>',
				array('attrName' => 'bar')
			),
			array(
				'Twit me at @foo.bar',
				'<t>Twit me at @foo.bar</t>'
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'Hit me at example@example.com',
				'Hit me at <a href="mailto:example@example.com">example@example.com</a>'
			),
			array(
				'Hit me at example@example.com',
				'Hit me at <a href="mailto:example@example.com">example@example.com</a>',
				array('tagName' => 'FOO')
			),
			array(
				'Hit me at example@example.com',
				'Hit me at <a href="mailto:example@example.com">example@example.com</a>',
				array('tagName' => 'FOO')
			),
		);
	}
}