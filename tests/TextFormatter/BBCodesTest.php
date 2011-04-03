<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../Test.php';

class BBCodesTest extends Test
{
	public function testOverlappingTagsAreSortedOut()
	{
		$this->cb->BBCodes->addBBCode(
			'x',
			array('attrs' => array('foo' => array('type' => 'text')))
		);

		$this->assertParsing(
			'[x foo="[b]bar[/b]" /]',
			'<rt><X foo="[b]bar[/b]">[x foo=&quot;[b]bar[/b]&quot; /]</X></rt>'
		);
	}

	public function testBbcodeTagsCanUseAColonFollowedByDigitsAsASuffixToControlHowStartTagsAndEndTagsArePaired()
	{
		$this->cb->BBCodes->addBBCode('B', array('nestingLimit' => 1));

		$this->assertParsing(
			'[B:123]bold tags: [B]text[/B][/B:123]',
			'<rt><B><st>[B:123]</st>bold tags: [B]text[/B]<et>[/B:123]</et></B></rt>'
		);
	}
}