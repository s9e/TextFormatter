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
				'<rt>x <B><s>[b]</s>bold<e>[/b]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				}
			],
			[
				'x [B]BOLD[/B] y',
				'<rt>x <B><s>[B]</s>BOLD<e>[/B]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				}
			],
			[
				'x [B/] y',
				'<rt>x <B>[B/]</B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				}
			],
			[
				'x [B /] y',
				'<rt>x <B>[B /]</B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				}
			],
			[
				'x [B/[',
				'<pt>x [B/[</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				}
			],
			[
				'x [B/',
				'<pt>x [B/</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				}
			],
			[
				'x [B  ',
				'<pt>x [B  </pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				}
			],
			[
				'x [b]bold[/b] y',
				'<rt>x <FOO><s>[b]</s>bold<e>[/b]</e></FOO> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B', ['tagName' => 'FOO']);
					$constructor->tags->add('FOO');
				}
			],
			[
				'x [b y="foo"]bold[/b] y',
				'<rt>x <B y="foo"><s>[b y="foo"]</s>bold<e>[/b]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B')->attributes->add('y');
				}
			],
			[
				'x [b Y="foo"]bold[/b] y',
				'<rt>x <B y="foo"><s>[b Y="foo"]</s>bold<e>[/b]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B')->attributes->add('y');
				}
			],
			[
				'x [b x="bar" y="foo"]bold[/b] y',
				'<rt>x <B x="bar" y="foo"><s>[b x="bar" y="foo"]</s>bold<e>[/b]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				}
			],
			[
				"x [b x='bar' y='foo']bold[/b] y",
				'<rt>x <B x="bar" y="foo"><s>[b x=\'bar\' y=\'foo\']</s>bold<e>[/b]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				}
			],
			[
				'x [b x=bar y=foo]bold[/b] y',
				'<rt>x <B x="bar" y="foo"><s>[b x=bar y=foo]</s>bold<e>[/b]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				}
			],
			[
				'x [b=1]bold[/b] y',
				'<rt>x <B b="1"><s>[b=1]</s>bold<e>[/b]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B')->attributes->add('b');
				}
			],
			[
				'x [b=1 /] y',
				'<rt>x <B b="1">[b=1 /]</B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B')->attributes->add('b');
				}
			],
			[
				'x [url=http://example.org/]example[/url] y',
				'<rt>x <URL url="http://example.org/"><s>[url=http://example.org/]</s>example<e>[/url]</e></URL> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('URL');
					$constructor->tags->add('URL')->attributes->add('url');
				}
			],
			[
				"x [b x='\"bar\"'/] y",
				'<rt>x <B x="&quot;bar&quot;">[b x=\'"bar"\'/]</B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x="\'bar\'"/] y',
				'<rt>x <B x="\'bar\'">[b x="\'bar\'"/]</B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x="\\""/] y',
				'<rt>x <B x="&quot;">[b x="\\""/]</B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				"x [b x='\\''/] y",
				"<rt>x <B x=\"'\">[b x='\\''/]</B> y</rt>",
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x="\\\\\\""/] y',
				'<rt>x <B x="\\&quot;">[b x="\\\\\\""/]</B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x=" ] y',
				'<pt>x [b x=" ] y</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				"x [b x=' ] y",
				"<pt>x [b x=' ] y</pt>",
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x!',
				'<pt>x [b x!</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x][/b] y',
				'<rt>x <B><s>[b x]</s><e>[/b]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B')->attributes->add('x')->required = false;
				}
			],
			[
				'x [b foo x=1][/b] y',
				'<rt>x <B x="1"><s>[b foo x=1]</s><e>[/b]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x')->required = false;
					$attributes->add('foo')->required = false;
				}
			],
			[
				'x [b x',
				'<pt>x [b x</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x')->required = false;
				}
			],
			[
				'x [b x=',
				'<pt>x [b x=</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [b x=bar',
				'<pt>x [b x=bar</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				}
			],
			[
				'x [B="foo" /]',
				'<rt>x <B b="foo">[B="foo" /]</B></rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('b');
				}
			],
			[
				'x [b="foo" /]',
				'<rt>x <B x="foo">[b="foo" /]</B></rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B')->defaultAttribute = 'x';
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('b')->required = false;
					$attributes->add('x')->required = false;
				}
			],
			[
				'x [URL]http://localhost[/URL] y',
				'<rt>x <URL url="http://localhost"><s>[URL]</s>http://localhost<e>[/URL]</e></URL> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('URL')->contentAttributes[] = 'url';
					$attributes = $constructor->tags->add('URL')->attributes;
					$attributes->add('url');
				}
			],
			[
				'x [URL=http://127.0.0.1]http://localhost[/URL] y',
				'<rt>x <URL url="http://127.0.0.1"><s>[URL=http://127.0.0.1]</s>http://localhost<e>[/URL]</e></URL> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('URL')->contentAttributes[] = 'url';
					$attributes = $constructor->tags->add('URL')->attributes;
					$attributes->add('url');
				}
			],
			[
				'x [URL]http://localhost y',
				'<pt>x [URL]http://localhost y</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('URL')->contentAttributes[] = 'url';
					$attributes = $constructor->tags->add('URL')->attributes;
					$attributes->add('url');
				}
			],
			[
				'[C:123]foo[/C][/C:123]',
				'<rt><C><s>[C:123]</s>foo[/C]<e>[/C:123]</e></C></rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('C');
					$constructor->tags->add('C');
				}
			],
			[
				'[C]foo[/C:123][/C]',
				'<rt><C><s>[C]</s>foo[/C:123]<e>[/C]</e></C></rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('C');
					$constructor->tags->add('C');
				}
			],
			[
				'[C:123]foo[/C][/c:123]',
				'<rt><C><s>[C:123]</s>foo[/C]<e>[/c:123]</e></C></rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('C');
					$constructor->tags->add('C');
				}
			],
			[
				'[C:123]foo[/C]',
				'<pt>[C:123]foo[/C]</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('C');
					$constructor->tags->add('C');
				}
			],
			[
				'[PHP]...[/PHP]',
				'<rt><CODE lang="php"><s>[PHP]</s>...<e>[/PHP]</e></CODE></rt>',
				[],
				function ($constructor)
				{
					$bbcode = $constructor->BBCodes->add('PHP');
					$bbcode->predefinedAttributes['lang'] = 'php';
					$bbcode->tagName = 'CODE';

					$constructor->tags->add('CODE')->attributes->add('lang');
				}
			],
			[
				'[PHP lang=php4]...[/PHP]',
				'<rt><CODE lang="php4"><s>[PHP lang=php4]</s>...<e>[/PHP]</e></CODE></rt>',
				[],
				function ($constructor)
				{
					$bbcode = $constructor->BBCodes->add('PHP');
					$bbcode->predefinedAttributes['lang'] = 'php';
					$bbcode->tagName = 'CODE';

					$constructor->tags->add('CODE')->attributes->add('lang');
				}
			],
			[
				'x [IMG=http://localhost/foo.png /] y',
				'<rt>x <IMG src="http://localhost/foo.png">[IMG=http://localhost/foo.png /]</IMG> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('IMG')->defaultAttribute = 'src';
					$constructor->tags->add('IMG')->attributes->add('src');
				}
			],
			[
				'x [b]...[/b] y',
				'<rt>x <B><s>[b]</s>...<e>[/b]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B')->forceLookahead = true;
					$constructor->tags->add('B');
				}
			],
			[
				'x [b:123]...[/b:123] y',
				'<rt>x <B><s>[b:123]</s>...<e>[/b:123]</e></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B')->forceLookahead = true;
					$constructor->tags->add('B');
				}
			],
			[
				'x [b:123]...[/b:456] y',
				'<pt>x [b:123]...[/b:456] y</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B')->forceLookahead = true;
					$constructor->tags->add('B');
				}
			],
			[
				'x [b]...[/i] y',
				'<pt>x [b]...[/i] y</pt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B')->forceLookahead = true;
					$constructor->tags->add('B');
				}
			],
			[
				'x [b]...[/b] [b]...[/i] y',
				'<rt>x <B><s>[b]</s>...<e>[/b]</e></B> [b]...[/i] y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B')->forceLookahead = true;
					$constructor->tags->add('B');
				}
			],
			[
				'x [img/] y',
				'<rt>x <IMG>[img/]</IMG> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('IMG')->forceLookahead = true;
					$constructor->tags->add('IMG');
				}
			],
		];
	}
}