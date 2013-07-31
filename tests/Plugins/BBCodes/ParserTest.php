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
				'<rt>x <B><st>[b]</st>bold<et>[/b]</et></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				}
			],
			[
				'x [B]BOLD[/B] y',
				'<rt>x <B><st>[B]</st>BOLD<et>[/B]</et></B> y</rt>',
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
				'<rt>x <FOO><st>[b]</st>bold<et>[/b]</et></FOO> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B', ['tagName' => 'FOO']);
					$constructor->tags->add('FOO');
				}
			],
			[
				'x [b y="foo"]bold[/b] y',
				'<rt>x <B y="foo"><st>[b y="foo"]</st>bold<et>[/b]</et></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B')->attributes->add('y');
				}
			],
			[
				'x [b Y="foo"]bold[/b] y',
				'<rt>x <B y="foo"><st>[b Y="foo"]</st>bold<et>[/b]</et></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B')->attributes->add('y');
				}
			],
			[
				'x [b x="bar" y="foo"]bold[/b] y',
				'<rt>x <B x="bar" y="foo"><st>[b x="bar" y="foo"]</st>bold<et>[/b]</et></B> y</rt>',
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
				'<rt>x <B x="bar" y="foo"><st>[b x=\'bar\' y=\'foo\']</st>bold<et>[/b]</et></B> y</rt>',
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
				'<rt>x <B x="bar" y="foo"><st>[b x=bar y=foo]</st>bold<et>[/b]</et></B> y</rt>',
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
				'<rt>x <B b="1"><st>[b=1]</st>bold<et>[/b]</et></B> y</rt>',
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
				'<rt>x <URL url="http://example.org/"><st>[url=http://example.org/]</st>example<et>[/url]</et></URL> y</rt>',
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
				'<rt>x <B><st>[b x]</st><et>[/b]</et></B> y</rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B')->attributes->add('x')->required = false;
				}
			],
			[
				'x [b foo x=1][/b] y',
				'<rt>x <B x="1"><st>[b foo x=1]</st><et>[/b]</et></B> y</rt>',
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
				'<rt>x <URL url="http://localhost"><st>[URL]</st>http://localhost<et>[/URL]</et></URL> y</rt>',
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
				'<rt>x <URL url="http://127.0.0.1"><st>[URL=http://127.0.0.1]</st>http://localhost<et>[/URL]</et></URL> y</rt>',
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
				'<rt><C><st>[C:123]</st>foo[/C]<et>[/C:123]</et></C></rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('C');
					$constructor->tags->add('C');
				}
			],
			[
				'[C]foo[/C:123][/C]',
				'<rt><C><st>[C]</st>foo[/C:123]<et>[/C]</et></C></rt>',
				[],
				function ($constructor)
				{
					$constructor->BBCodes->add('C');
					$constructor->tags->add('C');
				}
			],
			[
				'[C:123]foo[/C][/c:123]',
				'<rt><C><st>[C:123]</st>foo[/C]<et>[/c:123]</et></C></rt>',
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
				'<rt><CODE lang="php"><st>[PHP]</st>...<et>[/PHP]</et></CODE></rt>',
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
				'<rt><CODE lang="php4"><st>[PHP lang=php4]</st>...<et>[/PHP]</et></CODE></rt>',
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
		];
	}
}