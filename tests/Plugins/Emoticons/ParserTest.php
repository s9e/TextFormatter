<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoticons;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Emoticons\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoticons\Parser
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
				':)',
				'<r><E>:)</E></r>',
				array(),
				function ($configurator)
				{
					$configurator->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			),
			array(
				':)',
				'<r><EMOTE>:)</EMOTE></r>',
				array('tagName' => 'EMOTE'),
				function ($configurator)
				{
					$configurator->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			),
			array(
				':)',
				'<r><E>:)</E></r>',
				array(),
				function ($configurator)
				{
					$configurator->Emoticons->notAfter = '\\w';
					$configurator->Emoticons->add(':)', '<img src="s.png"/>');
				}
			),
			array(
				' :)',
				'<r> <E>:)</E></r>',
				array(),
				function ($configurator)
				{
					$configurator->Emoticons->notAfter = '\\w';
					$configurator->Emoticons->add(':)', '<img src="s.png"/>');
				}
			),
			array(
				'x:)',
				'<t>x:)</t>',
				array(),
				function ($configurator)
				{
					$configurator->Emoticons->notAfter = '\\w';
					$configurator->Emoticons->add(':)', '<img src="s.png"/>');
				}
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				':)',
				'<img src="s.png" alt=":)">',
				array(),
				function ($configurator)
				{
					$configurator->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			),
			array(
				':)',
				'<img src="s.png" alt=":)">',
				array('tagName' => 'EMOTE'),
				function ($configurator)
				{
					$configurator->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			),
			array(
				":')",
				'<img src="s.png">',
				array(),
				function ($configurator)
				{
					$configurator->Emoticons->add(":')", '<img src="s.png"/>');
				}
			),
			array(
				':")',
				'<img src="s.png">',
				array(),
				function ($configurator)
				{
					$configurator->Emoticons->add(':")', '<img src="s.png"/>');
				}
			),
			array(
				'\':")',
				'<img src="s.png">',
				array(),
				function ($configurator)
				{
					$configurator->Emoticons->add('\':")', '<img src="s.png"/>');
				}
			),
		);
	}
}