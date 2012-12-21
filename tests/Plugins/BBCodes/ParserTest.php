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
		);
	}
}