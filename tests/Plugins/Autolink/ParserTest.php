<?php

namespace s9e\TextFormatter\Tests\Plugins\Autolink;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Autolink\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Autolink\Parser
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
		$_this = $this;

		return array(
			array(
				'Go to http://www.example.com/ for more info',
				'<r>Go to <URL url="http://www.example.com/">http://www.example.com/</URL> for more info</r>'
			),
			array(
				'Go to http://www.example.com/ for more info',
				'<r>Go to <URL url="http://www.example.com/">http://www.example.com/</URL> for more info</r>'
			),
			array(
				'Go to http://www.example.com/ for more info',
				'<r>Go to <FOO url="http://www.example.com/">http://www.example.com/</FOO> for more info</r>',
				array('tagName' => 'FOO')
			),
			array(
				'Go to http://www.example.com/ for more info',
				'<r>Go to <URL bar="http://www.example.com/">http://www.example.com/</URL> for more info</r>',
				array('attrName' => 'bar')
			),
			array(
				'Go to foo://www.example.com/ for more info',
				'<t>Go to foo://www.example.com/ for more info</t>'
			),
			array(
				'Go to foo://www.example.com/ for more info',
				'<r>Go to <URL url="foo://www.example.com/">foo://www.example.com/</URL> for more info</r>',
				array(),
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('foo');
				}
			),
			array(
				'Go to http://www.example.com/. Like, now.',
				'<r>Go to <URL url="http://www.example.com/">http://www.example.com/</URL>. Like, now.</r>'
			),
			array(
				'Go to http://www.example.com/foo! Like, right now!',
				'<r>Go to <URL url="http://www.example.com/foo">http://www.example.com/foo</URL>! Like, right now!</r>'
			),
			array(
				'Go to http://www.example.com/?foo= for more info',
				'<r>Go to <URL url="http://www.example.com/?foo=">http://www.example.com/?foo=</URL> for more info</r>'
			),
			array(
				'Mars (http://en.wikipedia.org/wiki/Mars_(planet)) is the fourth planet from the Sun',
				'<r>Mars (<URL url="http://en.wikipedia.org/wiki/Mars_%28planet%29">http://en.wikipedia.org/wiki/Mars_(planet)</URL>) is the fourth planet from the Sun</r>'
			),
			array(
				'Mars (http://en.wikipedia.org/wiki/Mars) can mean many things',
				'<r>Mars (<URL url="http://en.wikipedia.org/wiki/Mars">http://en.wikipedia.org/wiki/Mars</URL>) can mean many things</r>'
			),
			array(
				/** @link http://area51.phpbb.com/phpBB/viewtopic.php?f=75&t=32142 */
				'http://www.xn--lyp-plada.com for http://www.älypää.com',
				'<r><URL url="http://www.xn--lyp-plada.com">http://www.xn--lyp-plada.com</URL> for <URL url="http://www.xn--lyp-plada.com">http://www.älypää.com</URL></r>',
				array(),
				function () use ($_this)
				{
					if (!function_exists('idn_to_ascii'))
					{
						$_this->markTestSkipped('idn_to_ascii() is required.');
					}
				}
			),
			array(
				'http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen for http://en.wikipedia.org/wiki/Matti_Nykänen',
				'<r><URL url="http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen">http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen</URL> for <URL url="http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen">http://en.wikipedia.org/wiki/Matti_Nykänen</URL></r>'
			),
			array(
				'Check this out http://en.wikipedia.org/wiki/♥',
				'<r>Check this out <URL url="http://en.wikipedia.org/wiki/%E2%99%A5">http://en.wikipedia.org/wiki/♥</URL></r>'
			),
			array(
				'Check those out: http://example.com/list.php?cat[]=1&cat[]=2',
				'<r>Check those out: <URL url="http://example.com/list.php?cat%5B%5D=1&amp;cat%5B%5D=2">http://example.com/list.php?cat[]=1&amp;cat[]=2</URL></r>'
			),
			array(
				'Check those out: http://example.com/list.php?cat[1a]=1&cat[1b]=2',
				'<r>Check those out: <URL url="http://example.com/list.php?cat%5B1a%5D=1&amp;cat%5B1b%5D=2">http://example.com/list.php?cat[1a]=1&amp;cat[1b]=2</URL></r>'
			),
			array(
				'[url=http://example.com]Non-existent URL tag[/url]',
				'<r>[url=<URL url="http://example.com">http://example.com</URL>]Non-existent URL tag[/url]</r>'
			),
			array(
				'Link in brackets: [http://example.com/foo] --',
				'<r>Link in brackets: [<URL url="http://example.com/foo">http://example.com/foo</URL>] --</r>'
			),
			array(
				'Link in brackets: [http://example.com/foo?a[]=1] --',
				'<r>Link in brackets: [<URL url="http://example.com/foo?a%5B%5D=1">http://example.com/foo?a[]=1</URL>] --</r>'
			),
			array(
				'Link in angle brackets: <http://example.com/foo>',
				'<r>Link in angle brackets: &lt;<URL url="http://example.com/foo">http://example.com/foo</URL>&gt;</r>'
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'Go to http://www.example.com/ for more info',
				'Go to <a href="http://www.example.com/">http://www.example.com/</a> for more info'
			),
			array(
				'Go to http://www.example.com/ for more info',
				'Go to <a href="http://www.example.com/">http://www.example.com/</a> for more info',
				array('tagName' => 'FOO')
			),
			array(
				'Go to http://www.example.com/ for more info',
				'Go to <a href="http://www.example.com/">http://www.example.com/</a> for more info',
				array('attrName' => 'bar')
			),
			array(
				'Check this out http://en.wikipedia.org/wiki/♥',
				'Check this out <a href="http://en.wikipedia.org/wiki/%E2%99%A5">http://en.wikipedia.org/wiki/♥</a>'
			),
		);
	}
}