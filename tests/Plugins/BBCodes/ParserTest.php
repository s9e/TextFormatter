<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\BBCodes\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Parser
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

	public function getParsingTests()
	{
		return array(
			array(
				'x [b]bold[/b] y',
				'<r>x <B><s>[b]</s>bold<e>[/b]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			),
			array(
				'x [B]BOLD[/B] y',
				'<r>x <B><s>[B]</s>BOLD<e>[/B]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			),
			array(
				'x [B/] y',
				'<r>x <B>[B/]</B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			),
			array(
				'x [B /] y',
				'<r>x <B>[B /]</B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			),
			array(
				'x [B/[',
				'<t>x [B/[</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			),
			array(
				'x [B/',
				'<t>x [B/</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			),
			array(
				'x [B  ',
				'<t>x [B  </t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			),
			array(
				'x [b]bold[/b] y',
				'<r>x <FOO><s>[b]</s>bold<e>[/b]</e></FOO> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B', array('tagName' => 'FOO'));
					$configurator->tags->add('FOO');
				}
			),
			array(
				'x [b y="foo"]bold[/b] y',
				'<r>x <B y="foo"><s>[b y="foo"]</s>bold<e>[/b]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B')->attributes->add('y');
				}
			),
			array(
				'x [b Y="foo"]bold[/b] y',
				'<r>x <B y="foo"><s>[b Y="foo"]</s>bold<e>[/b]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B')->attributes->add('y');
				}
			),
			array(
				'x [b x="bar" y="foo"]bold[/b] y',
				'<r>x <B x="bar" y="foo"><s>[b x="bar" y="foo"]</s>bold<e>[/b]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				}
			),
			array(
				"x [b x='bar' y='foo']bold[/b] y",
				'<r>x <B x="bar" y="foo"><s>[b x=\'bar\' y=\'foo\']</s>bold<e>[/b]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				}
			),
			array(
				'x [b x=bar y=foo]bold[/b] y',
				'<r>x <B x="bar" y="foo"><s>[b x=bar y=foo]</s>bold<e>[/b]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				}
			),
			array(
				'x [b=1]bold[/b] y',
				'<r>x <B b="1"><s>[b=1]</s>bold<e>[/b]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B')->attributes->add('b');
				}
			),
			array(
				'x [b=1 /] y',
				'<r>x <B b="1">[b=1 /]</B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B')->attributes->add('b');
				}
			),
			array(
				'x [url=http://example.org/]example[/url] y',
				'<r>x <URL url="http://example.org/"><s>[url=http://example.org/]</s>example<e>[/url]</e></URL> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('URL');
					$configurator->tags->add('URL')->attributes->add('url');
				}
			),
			array(
				"x [b x='\"bar\"'/] y",
				'<r>x <B x="&quot;bar&quot;">[b x=\'"bar"\'/]</B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			),
			array(
				'x [b x="\'bar\'"/] y',
				'<r>x <B x="\'bar\'">[b x="\'bar\'"/]</B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			),
			array(
				'x [b x="\\""/] y',
				'<r>x <B x="&quot;">[b x="\\""/]</B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			),
			array(
				"x [b x='\\''/] y",
				"<r>x <B x=\"'\">[b x='\\''/]</B> y</r>",
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			),
			array(
				'x [b x="\\\\\\""/] y',
				'<r>x <B x="\\&quot;">[b x="\\\\\\""/]</B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			),
			array(
				'x [b x=" ] y',
				'<t>x [b x=" ] y</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			),
			array(
				"x [b x=' ] y",
				"<t>x [b x=' ] y</t>",
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			),
			array(
				'x [b x!',
				'<t>x [b x!</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			),
			array(
				'x [b x][/b] y',
				'<r>x <B><s>[b x]</s><e>[/b]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B')->attributes->add('x')->required = false;
				}
			),
			array(
				'x [b foo x=1][/b] y',
				'<r>x <B x="1"><s>[b foo x=1]</s><e>[/b]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x')->required = false;
					$attributes->add('foo')->required = false;
				}
			),
			array(
				'x [b x',
				'<t>x [b x</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x')->required = false;
				}
			),
			array(
				'x [b x=',
				'<t>x [b x=</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			),
			array(
				'x [b x=bar',
				'<t>x [b x=bar</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			),
			array(
				'x [B="foo" /]',
				'<r>x <B b="foo">[B="foo" /]</B></r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('b');
				}
			),
			array(
				'x [b="foo" /]',
				'<r>x <B x="foo">[b="foo" /]</B></r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->defaultAttribute = 'x';
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('b')->required = false;
					$attributes->add('x')->required = false;
				}
			),
			array(
				'x [URL]http://localhost[/URL] y',
				'<r>x <URL url="http://localhost"><s>[URL]</s>http://localhost<e>[/URL]</e></URL> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('URL')->contentAttributes[] = 'url';
					$attributes = $configurator->tags->add('URL')->attributes;
					$attributes->add('url');
				}
			),
			array(
				'x [URL=http://127.0.0.1]http://localhost[/URL] y',
				'<r>x <URL url="http://127.0.0.1"><s>[URL=http://127.0.0.1]</s>http://localhost<e>[/URL]</e></URL> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('URL')->contentAttributes[] = 'url';
					$attributes = $configurator->tags->add('URL')->attributes;
					$attributes->add('url');
				}
			),
			array(
				'x [URL]http://localhost y',
				'<t>x [URL]http://localhost y</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('URL')->contentAttributes[] = 'url';
					$attributes = $configurator->tags->add('URL')->attributes;
					$attributes->add('url');
				}
			),
			array(
				'[C:123]foo[/C][/C:123]',
				'<r><C><s>[C:123]</s>foo[/C]<e>[/C:123]</e></C></r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('C');
					$configurator->tags->add('C');
				}
			),
			array(
				'[C]foo[/C:123][/C]',
				'<r><C><s>[C]</s>foo[/C:123]<e>[/C]</e></C></r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('C');
					$configurator->tags->add('C');
				}
			),
			array(
				'[C:123]foo[/C][/c:123]',
				'<r><C><s>[C:123]</s>foo[/C]<e>[/c:123]</e></C></r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('C');
					$configurator->tags->add('C');
				}
			),
			array(
				'[C:123]foo[/C]',
				'<t>[C:123]foo[/C]</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('C');
					$configurator->tags->add('C');
				}
			),
			array(
				'[PHP]...[/PHP]',
				'<r><CODE lang="php"><s>[PHP]</s>...<e>[/PHP]</e></CODE></r>',
				array(),
				function ($configurator)
				{
					$bbcode = $configurator->BBCodes->add('PHP');
					$bbcode->predefinedAttributes['lang'] = 'php';
					$bbcode->tagName = 'CODE';

					$configurator->tags->add('CODE')->attributes->add('lang');
				}
			),
			array(
				'[PHP lang=php4]...[/PHP]',
				'<r><CODE lang="php4"><s>[PHP lang=php4]</s>...<e>[/PHP]</e></CODE></r>',
				array(),
				function ($configurator)
				{
					$bbcode = $configurator->BBCodes->add('PHP');
					$bbcode->predefinedAttributes['lang'] = 'php';
					$bbcode->tagName = 'CODE';

					$configurator->tags->add('CODE')->attributes->add('lang');
				}
			),
			array(
				'x [IMG=http://localhost/foo.png /] y',
				'<r>x <IMG src="http://localhost/foo.png">[IMG=http://localhost/foo.png /]</IMG> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('IMG')->defaultAttribute = 'src';
					$configurator->tags->add('IMG')->attributes->add('src');
				}
			),
			array(
				'x [b]...[/b] y',
				'<r>x <B><s>[b]</s>...<e>[/b]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->forceLookahead = true;
					$configurator->tags->add('B');
				}
			),
			array(
				'x [b:123]...[/b:123] y',
				'<r>x <B><s>[b:123]</s>...<e>[/b:123]</e></B> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->forceLookahead = true;
					$configurator->tags->add('B');
				}
			),
			array(
				'x [b:123]...[/b:456] y',
				'<t>x [b:123]...[/b:456] y</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->forceLookahead = true;
					$configurator->tags->add('B');
				}
			),
			array(
				'x [b]...[/i] y',
				'<t>x [b]...[/i] y</t>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->forceLookahead = true;
					$configurator->tags->add('B');
				}
			),
			array(
				'x [b]...[/b] [b]...[/i] y',
				'<r>x <B><s>[b]</s>...<e>[/b]</e></B> [b]...[/i] y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->forceLookahead = true;
					$configurator->tags->add('B');
				}
			),
			array(
				'x [img/] y',
				'<r>x <IMG>[img/]</IMG> y</r>',
				array(),
				function ($configurator)
				{
					$configurator->BBCodes->add('IMG')->forceLookahead = true;
					$configurator->tags->add('IMG');
				}
			),
		);
	}
}