<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\BBCodes\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				'x [b]bold[/b] y',
				'<rt>x <B><st>[b]</st>bold<et>[/b]</et></B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				},
			),
			array(
				'x [B]BOLD[/B] y',
				'<rt>x <B><st>[B]</st>BOLD<et>[/B]</et></B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				},
			),
			array(
				'x [B/] y',
				'<rt>x <B>[B/]</B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				},
			),
			array(
				'x [B /] y',
				'<rt>x <B>[B /]</B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				},
			),
			array(
				'x [B/[',
				'<pt>x [B/[</pt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				},
			),
			array(
				'x [B/',
				'<pt>x [B/</pt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				},
			),
			array(
				'x [B  ',
				'<pt>x [B  </pt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B');
				},
			),
			array(
				'x [b]bold[/b] y',
				'<rt>x <FOO><st>[b]</st>bold<et>[/b]</et></FOO> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B', array('tagName' => 'FOO'));
					$constructor->tags->add('FOO');
				},
			),
			array(
				'x [b y="foo"]bold[/b] y',
				'<rt>x <B y="foo"><st>[b y="foo"]</st>bold<et>[/b]</et></B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B')->attributes->add('y');
				},
			),
			array(
				'x [b Y="foo"]bold[/b] y',
				'<rt>x <B y="foo"><st>[b Y="foo"]</st>bold<et>[/b]</et></B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$constructor->tags->add('B')->attributes->add('y');
				},
			),
			array(
				'x [b x="bar" y="foo"]bold[/b] y',
				'<rt>x <B x="bar" y="foo"><st>[b x="bar" y="foo"]</st>bold<et>[/b]</et></B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				},
			),
			array(
				"x [b x='bar' y='foo']bold[/b] y",
				'<rt>x <B x="bar" y="foo"><st>[b x=\'bar\' y=\'foo\']</st>bold<et>[/b]</et></B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				},
			),
			array(
				'x [b x=bar y=foo]bold[/b] y',
				'<rt>x <B x="bar" y="foo"><st>[b x=bar y=foo]</st>bold<et>[/b]</et></B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('y');
				},
			),
			array(
				"x [b x='\"bar\"'/] y",
				'<rt>x <B x="&quot;bar&quot;">[b x=\'"bar"\'/]</B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				'x [b x="\'bar\'"/] y',
				'<rt>x <B x="\'bar\'">[b x="\'bar\'"/]</B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				'x [b x="\\""/] y',
				'<rt>x <B x="&quot;">[b x="\\""/]</B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				"x [b x='\\''/] y",
				"<rt>x <B x=\"'\">[b x='\\''/]</B> y</rt>",
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				'x [b x="\\\\\\""/] y',
				'<rt>x <B x="\\&quot;">[b x="\\\\\\""/]</B> y</rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				'x [b x=" ] y',
				'<pt>x [b x=" ] y</pt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				"x [b x=' ] y",
				"<pt>x [b x=' ] y</pt>",
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				'x [b x!',
				'<pt>x [b x!</pt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				'x [b x]',
				'<rt>x <B><st>[b x]</st></rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				'x [b foo x=1]',
				'<rt>x <B x="1"><st>[b foo x=1]</st></rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
					$attributes->add('foo');
				},
			),
			array(
				'x [b x',
				'<pt>x [b x</pt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				'x [b x=',
				'<pt>x [b x=</pt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				'x [b x=bar',
				'<pt>x [b x=bar</pt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('x');
				},
			),
			array(
				'x [B="foo" /]',
				'<rt>x <B b="foo">[B="foo" /]</B></rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B');
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('b');
				},
			),
			array(
				'x [b="foo" /]',
				'<rt>x <B x="foo">[b="foo" /]</B></rt>',
				array(),
				function ($constructor)
				{
					$constructor->BBCodes->add('B')->defaultAttribute = 'x';
					$attributes = $constructor->tags->add('B')->attributes;
					$attributes->add('b');
					$attributes->add('x');
				},
			),
		);
	}
}