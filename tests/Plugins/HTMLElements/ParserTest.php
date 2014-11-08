<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLElements;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\HTMLElements\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLElements\Parser
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
				'x <b>bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b><s>&lt;b&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			),
			array(
				'x <b>bold</b> x',
				'<r xmlns:foo="urn:s9e:TextFormatter:foo">x <foo:b><s>&lt;b&gt;</s>bold<e>&lt;/b&gt;</e></foo:b> x</r>',
				array('prefix' => 'foo'),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			),
			array(
				'x <b title="is bold">bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b title="is bold"><s>&lt;b title="is bold"&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
					$configurator->HTMLElements->allowAttribute('b', 'title');
				}
			),
			array(
				'x <b title="is bold">bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b><s>&lt;b title="is bold"&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			),
			array(
				'x <B>bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b><s>&lt;B&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			),
			array(
				'x <b Title="is bold">bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b title="is bold"><s>&lt;b Title="is bold"&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
					$configurator->HTMLElements->allowAttribute('b', 'title');
				}
			),
			array(
				'x<br/>y',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x<html:br><s>&lt;br/&gt;</s></html:br>y</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('br');
				}
			),
			array(
				'x<br />y',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x<html:br><s>&lt;br /&gt;</s></html:br>y</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('br');
				}
			),
			array(
				'x <input disabled name=foo readonly /> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:input disabled="disabled" name="foo" readonly="readonly"><s>&lt;input disabled name=foo readonly /&gt;</s></html:input> x</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('input');
					$configurator->HTMLElements->allowAttribute('input', 'disabled');
					$configurator->HTMLElements->allowAttribute('input', 'name');
					$configurator->HTMLElements->allowAttribute('input', 'readonly');
				}
			),
			array(
				'x <b title = "is bold">bold</b> x',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:b><s>&lt;b title = "is bold"&gt;</s>bold<e>&lt;/b&gt;</e></html:b> x</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			),
			array(
				'x <b>...</b> y',
				'<r>x <BOLD><s>&lt;b&gt;</s>...<e>&lt;/b&gt;</e></BOLD> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->aliasElement('b', 'bold');
					$configurator->tags->add('bold');
				}
			),
			array(
				'x <a href="http://example.org">...</a> y',
				'<r>x <URL url="http://example.org"><s>&lt;a href="http://example.org"&gt;</s>...<e>&lt;/a&gt;</e></URL> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->aliasElement('a', 'url');
					$configurator->HTMLElements->aliasAttribute('a', 'href', 'url');

					$configurator->tags->add('URL')->attributes->add('url')->filterChain->append(
						$configurator->attributeFilters['#url']
					);
				}
			),
			array(
				'x <span title="foo">...</b> <div title="bar">...</div> y',
				'<r xmlns:html="urn:s9e:TextFormatter:html">x <html:span data-title="foo"><s>&lt;span title="foo"&gt;</s>...&lt;/b&gt; <html:div title="bar"><s>&lt;div title="bar"&gt;</s>...<e>&lt;/div&gt;</e></html:div> y</html:span></r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('span');
					$configurator->HTMLElements->allowAttribute('span', 'data-title');
					$configurator->HTMLElements->allowAttribute('span', 'title');
					$configurator->HTMLElements->allowElement('div');
					$configurator->HTMLElements->allowAttribute('div', 'data-title');
					$configurator->HTMLElements->allowAttribute('div', 'title');

					$configurator->HTMLElements->aliasAttribute('span', 'title', 'data-title');
				}
			),
			array(
				'<img alt="">',
				'<r xmlns:html="urn:s9e:TextFormatter:html"><html:img alt=""><s>&lt;img alt=""&gt;</s></html:img></r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('img');
					$configurator->HTMLElements->allowAttribute('img', 'alt');
				}
			),
			array(
				'<img data-crc32="123">',
				'<r xmlns:html="urn:s9e:TextFormatter:html"><html:img data-crc32="123"><s>&lt;img data-crc32="123"&gt;</s></html:img></r>',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('img');
					$configurator->HTMLElements->allowAttribute('img', 'data-crc32');
				}
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'x <b>bold</b> x',
				'x <b>bold</b> x',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			),
			array(
				'x <b>bold</b> x',
				'x <b>bold</b> x',
				array('prefix' => 'foo'),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
				}
			),
			array(
				'x <b title="is bold">bold</b> x',
				'x <b title="is bold">bold</b> x',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('b');
					$configurator->HTMLElements->allowAttribute('b', 'title');
				}
			),
			array(
				'Break: <br/> :)',
				'Break: <br> :)',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('br');
					$configurator->rendering->type = 'html';
				}
			),
			array(
				'Break: <br/> :)',
				'Break: <br/> :)',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('br');
					$configurator->rendering->type = 'xhtml';
				}
			),
			array(
				'Div: <div/> :)',
				'Div: <div></div> :)',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('div');
					$configurator->rendering->type = 'html';
				}
			),
			array(
				'Div: <div/> :)',
				'Div: <div/> :)',
				array(),
				function ($configurator)
				{
					$configurator->HTMLElements->allowElement('div');
					$configurator->rendering->type = 'xhtml';
				}
			),
		);
	}
}