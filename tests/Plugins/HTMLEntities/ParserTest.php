<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLEntities;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\HTMLEntities\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLEntities\Parser
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
				'AT&amp;T',
				'<r>AT<HE char="&amp;">&amp;amp;</HE>T</r>'
			),
			array(
				'AT&amp;T',
				'<r>AT<FOO char="&amp;">&amp;amp;</FOO>T</r>',
				array('tagName' => 'FOO')
			),
			array(
				'AT&amp;T',
				'<r>AT<HE bar="&amp;">&amp;amp;</HE>T</r>',
				array('attrName' => 'bar')
			),
			array(
				'I &hearts; AT&amp;T',
				'<r>I <HE char="♥">&amp;hearts;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</r>'
			),
			array(
				'I &#x2665; AT&amp;T',
				'<r>I <HE char="♥">&amp;#x2665;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</r>'
			),
			array(
				'I &#9829; AT&amp;T',
				'<r>I <HE char="♥">&amp;#9829;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</r>'
			),
			array(
				'Some &unknown; entity',
				'<t>Some &amp;unknown; entity</t>'
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'AT&amp;T',
				'AT&amp;T',
			),
			array(
				'AT&amp;T',
				'AT&amp;T',
				array('tagName' => 'FOO')
			),
			array(
				'AT&amp;T',
				'AT&amp;T',
				array('attrName' => 'bar')
			),
			array(
				'I &hearts; AT&amp;T',
				'I ♥ AT&amp;T'
			),
			array(
				'Pok&eacute;man',
				'Pokéman'
			),
			array(
				'POK&Eacute;MAN',
				'POKÉMAN'
			),
		);
	}
}