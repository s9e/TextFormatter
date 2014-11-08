<?php

namespace s9e\TextFormatter\Tests\Plugins\Generic;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Generic\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Generic\Parser
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
				'Follow @twitter for more info',
				'<r>Follow <GAC9F10E2 username="twitter">@twitter</GAC9F10E2> for more info</r>',
				array(),
				function ($configurator)
				{
					$configurator->Generic->add(
						'/@(?<username>[a-z0-9_]{1,15})/i',
						'<a href="https://twitter.com/{@username}"><xsl:apply-templates/></a>'
					);
				}
			),
			array(
				'Some *emphasis*.',
				'<r>Some <G86655032><s>*</s>emphasis<e>*</e></G86655032>.</r>',
				array(),
				function ($configurator)
				{
					$configurator->Generic->add(
						'/\\*(.*?)\\*/',
						'<em>$1</em>'
					);
				}
			),
			array(
				'Markdown [link](http://example.com) style.',
				'<r>Markdown <G792685FB _2="http://example.com"><s>[</s>link<e>](http://example.com)</e></G792685FB> style.</r>',
				array(),
				function ($configurator)
				{
					$configurator->Generic->add(
						'#\\[(.*?)\\]\\((https?://.*?)\\)#i',
						'<a href="$2">$1</a>'
					);
				}
			),
			array(
				'Some *_bold_ emphasis* or _*emphasised* boldness_.',
				'<r>Some <G86655032><s>*</s><G74E475F4><s>_</s>bold<e>_</e></G74E475F4> emphasis<e>*</e></G86655032> or <G74E475F4><s>_</s><G86655032><s>*</s>emphasised<e>*</e></G86655032> boldness<e>_</e></G74E475F4>.</r>',
				array(),
				function ($configurator)
				{
					$configurator->Generic->add(
						'/\\*(.*?)\\*/',
						'<em>$1</em>'
					);
					$configurator->Generic->add(
						'/_(.*?)_/',
						'<b>$1</b>'
					);
				}
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'Follow @twitter for more info',
				'Follow <a href="https://twitter.com/twitter">@twitter</a> for more info',
				array(),
				function ($configurator)
				{
					$configurator->Generic->add(
						'/@(?<username>[a-z0-9_]{1,15})/i',
						'<a href="https://twitter.com/{@username}"><xsl:apply-templates/></a>'
					);
				}
			),
			array(
				'Some *emphasis*.',
				'Some <em>emphasis</em>.',
				array(),
				function ($configurator)
				{
					$configurator->Generic->add(
						'/\\*(.*?)\\*/',
						'<em>$1</em>'
					);
				}
			),
			array(
				'Markdown [link](http://example.com) style.',
				'Markdown <a href="http://example.com">link</a> style.',
				array(),
				function ($configurator)
				{
					$configurator->Generic->add(
						'#\\[(.*?)\\]\\((https?://.*?)\\)#i',
						'<a href="$2">$1</a>'
					);
				}
			),
			array(
				'Some *_bold_ emphasis* or _*emphasised* boldness_.',
				'Some <em><b>bold</b> emphasis</em> or <b><em>emphasised</em> boldness</b>.',
				array(),
				function ($configurator)
				{
					$configurator->Generic->add(
						'/\\*(.*?)\\*/',
						'<em>$1</em>'
					);
					$configurator->Generic->add(
						'/_(.*?)_/',
						'<b>$1</b>'
					);
				}
			),
		);
	}
}