<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\BBCodes\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;

	public function getParsingTests()
	{
		return [
			[
				'x [b]bold[/b] y',
				'<r>x <B><s>[b]</s>bold<e>[/b]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			],
			[
				'x [B]BOLD[/B] y',
				'<r>x <B><s>[B]</s>BOLD<e>[/B]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			],
			[
				'x [B/] y',
				'<r>x <B>[B/]</B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			],
			[
				'x [B /] y',
				'<r>x <B>[B /]</B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			],
			[
				'x [B/[',
				'<t>x [B/[</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			],
			[
				'x [B/',
				'<t>x [B/</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			],
			[
				'x [B  ',
				'<t>x [B  </t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B');
				}
			],
			[
				'x [b]bold[/b] y',
				'<r>x <FOO><s>[b]</s>bold<e>[/b]</e></FOO> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B', ['tagName' => 'FOO']);
					$configurator->tags->add('FOO');
				}
			],
			[
				'x [b y="foo"]bold[/b] y',
				'<r>x <B y="foo"><s>[b y="foo"]</s>bold<e>[/b]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B')->attributes->add('y');
				}
			],
			[
				'x [b Y="foo"]bold[/b] y',
				'<r>x <B y="foo"><s>[b Y="foo"]</s>bold<e>[/b]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B')->attributes->add('y');
				}
			],
			[
				'x [b x="bar" y="foo"]bold[/b] y',
				'<r>x <B x="bar" y="foo"><s>[b x="bar" y="foo"]</s>bold<e>[/b]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				}
			],
			[
				"x [b x='bar' y='foo']bold[/b] y",
				'<r>x <B x="bar" y="foo"><s>[b x=\'bar\' y=\'foo\']</s>bold<e>[/b]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				}
			],
			[
				'x [b x=bar y=foo]bold[/b] y',
				'<r>x <B x="bar" y="foo"><s>[b x=bar y=foo]</s>bold<e>[/b]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				}
			],
			[
				'x [b=1]bold[/b] y',
				'<r>x <B b="1"><s>[b=1]</s>bold<e>[/b]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B')->attributes->add('b');
				}
			],
			[
				'x [b=1 /] y',
				'<r>x <B b="1">[b=1 /]</B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B')->attributes->add('b');
				}
			],
			[
				'x [url=http://example.org/]example[/url] y',
				'<r>x <URL url="http://example.org/"><s>[url=http://example.org/]</s>example<e>[/url]</e></URL> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('URL');
					$configurator->tags->add('URL')->attributes->add('url');
				}
			],
			[
				"x [b x='\"bar\"'/] y",
				'<r>x <B x="&quot;bar&quot;">[b x=\'"bar"\'/]</B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x="\'bar\'"/] y',
				'<r>x <B x="\'bar\'">[b x="\'bar\'"/]</B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x="\\""/] y',
				'<r>x <B x="&quot;">[b x="\\""/]</B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				"x [b x='\\''/] y",
				"<r>x <B x=\"'\">[b x='\\''/]</B> y</r>",
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x="\\\\\\""/] y',
				'<r>x <B x="\\&quot;">[b x="\\\\\\""/]</B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x=" ] y',
				'<t>x [b x=" ] y</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				"x [b x=' ] y",
				"<t>x [b x=' ] y</t>",
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x!',
				'<t>x [b x!</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x][/b] y',
				'<r>x <B><s>[b x]</s><e>[/b]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$configurator->tags->add('B')->attributes->add('x')->required = false;
				}
			],
			[
				'x [b foo x=1][/b] y',
				'<r>x <B x="1"><s>[b foo x=1]</s><e>[/b]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x')->required = false;
					$attributes->add('foo')->required = false;
				}
			],
			[
				'x [b x',
				'<t>x [b x</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x')->required = false;
				}
			],
			[
				'x [b x=',
				'<t>x [b x=</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x=bar',
				'<t>x [b x=bar</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x="',
				'<t>x [b x="</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [B="foo" /]',
				'<r>x <B b="foo">[B="foo" /]</B></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B');
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('b');
				}
			],
			[
				'x [b="foo" /]',
				'<r>x <B x="foo">[b="foo" /]</B></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->defaultAttribute = 'x';
					$attributes = $configurator->tags->add('B')->attributes;
					$attributes->add('b')->required = false;
					$attributes->add('x')->required = false;
				}
			],
			[
				'x [URL]http://localhost[/URL] y',
				'<r>x <URL url="http://localhost"><s>[URL]</s>http://localhost<e>[/URL]</e></URL> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('URL')->contentAttributes[] = 'url';
					$attributes = $configurator->tags->add('URL')->attributes;
					$attributes->add('url');
				}
			],
			[
				'x [URL=http://127.0.0.1]http://localhost[/URL] y',
				'<r>x <URL url="http://127.0.0.1"><s>[URL=http://127.0.0.1]</s>http://localhost<e>[/URL]</e></URL> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('URL')->contentAttributes[] = 'url';
					$attributes = $configurator->tags->add('URL')->attributes;
					$attributes->add('url');
				}
			],
			[
				'x [URL]http://localhost y',
				'<t>x [URL]http://localhost y</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('URL')->contentAttributes[] = 'url';
					$attributes = $configurator->tags->add('URL')->attributes;
					$attributes->add('url');
				}
			],
			[
				'[C:123]foo[/C][/C:123]',
				'<r><C><s>[C:123]</s>foo[/C]<e>[/C:123]</e></C></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('C');
					$configurator->tags->add('C');
				}
			],
			[
				'[C]foo[/C:123][/C]',
				'<r><C><s>[C]</s>foo[/C:123]<e>[/C]</e></C></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('C');
					$configurator->tags->add('C');
				}
			],
			[
				'[C:123]foo[/C][/c:123]',
				'<r><C><s>[C:123]</s>foo[/C]<e>[/c:123]</e></C></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('C');
					$configurator->tags->add('C');
				}
			],
			[
				'[C:123]foo[/C]',
				'<t>[C:123]foo[/C]</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('C');
					$configurator->tags->add('C');
				}
			],
			[
				'x [IMG=http://localhost/foo.png /] y',
				'<r>x <IMG src="http://localhost/foo.png">[IMG=http://localhost/foo.png /]</IMG> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('IMG')->defaultAttribute = 'src';
					$configurator->tags->add('IMG')->attributes->add('src');
				}
			],
			[
				'x [b]...[/b] y',
				'<r>x <B><s>[b]</s>...<e>[/b]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->forceLookahead = true;
					$configurator->tags->add('B');
				}
			],
			[
				'x [b:123]...[/b:123] y',
				'<r>x <B><s>[b:123]</s>...<e>[/b:123]</e></B> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->forceLookahead = true;
					$configurator->tags->add('B');
				}
			],
			[
				'x [b:123]...[/b:456] y',
				'<t>x [b:123]...[/b:456] y</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->forceLookahead = true;
					$configurator->tags->add('B');
				}
			],
			[
				'x [b]...[/i] y',
				'<t>x [b]...[/i] y</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->forceLookahead = true;
					$configurator->tags->add('B');
				}
			],
			[
				'x [b]...[/b] [b]...[/i] y',
				'<r>x <B><s>[b]</s>...<e>[/b]</e></B> [b]...[/i] y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('B')->forceLookahead = true;
					$configurator->tags->add('B');
				}
			],
			[
				'x [img/] y',
				'<r>x <IMG>[img/]</IMG> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('IMG')->forceLookahead = true;
					$configurator->tags->add('IMG');
				}
			],
			[
				'x [X/] [Z/] y',
				'<r>x [X/] <Z>[Z/]</Z> y</r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('Z');
					$configurator->tags->add('X');
					$configurator->tags->add('Z');
				}
			],
			[
				'[/X:',
				'<t>[/X:</t>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('X');
					$configurator->tags->add('X');
				}
			],
			[
				"[X\n\tfoo=1\n\tbar=2\n]..[/X]",
				"<r><X bar=\"2\" foo=\"1\"><s>[X\n\tfoo=1\n\tbar=2\n]</s>..<e>[/X]</e></X></r>",
				[],
				function ($configurator)
				{
					$configurator->BBCodes->add('X');
					$tag = $configurator->tags->add('X');
					$tag->attributes->add('foo');
					$tag->attributes->add('bar');
				}
			],
			[
				'[c][url][/c][url=http://example.org]...[/url]',
				'<r><C><s>[c]</s>[url]<e>[/c]</e></C><URL url="http://example.org"><s>[url=http://example.org]</s>...<e>[/url]</e></URL></r>',
				[],
				function ($configurator)
				{
					$configurator->BBCodes->addFromRepository('C');
					$configurator->BBCodes->addFromRepository('URL');
				}
			],
		];
	}
}