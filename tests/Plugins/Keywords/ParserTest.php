<?php

namespace s9e\TextFormatter\Tests\Plugins\Keywords;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Keywords\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Keywords\Parser
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

//	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				'foo bar baz',
				'<r><KEYWORD value="foo">foo</KEYWORD> bar baz</r>',
				array(),
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
				}
			),
			array(
				'foo bar baz',
				'<r><FOO value="foo">foo</FOO> bar baz</r>',
				array('tagName' => 'FOO'),
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
				}
			),
			array(
				'foo bar baz',
				'<r><KEYWORD foo="foo">foo</KEYWORD> bar baz</r>',
				array('attrName' => 'foo'),
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
				}
			),
			array(
				'foo foo foo',
				'<r><KEYWORD value="foo">foo</KEYWORD> <KEYWORD value="foo">foo</KEYWORD> <KEYWORD value="foo">foo</KEYWORD></r>',
				array(),
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
				}
			),
			array(
				'foo foo foo',
				'<r><KEYWORD value="foo">foo</KEYWORD> foo foo</r>',
				array(),
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
					$configurator->Keywords->onlyFirst = true;
				}
			),
			array(
				'foo foo bar bar',
				'<r><KEYWORD value="foo">foo</KEYWORD> foo <KEYWORD value="bar">bar</KEYWORD> bar</r>',
				array(),
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
					$configurator->Keywords->add('bar');
					$configurator->Keywords->onlyFirst = true;
				}
			),
		);
	}
}