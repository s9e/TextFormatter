<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\PluginConfig;

include_once __DIR__ . '/../Test.php';

class BBCodesTest extends Test
{
	protected function addBBCode($bbcodeName)
	{
		call_user_func_array(
			array(
				$this->cb->predefinedBBCodes,
				'add' . $bbcodeName
			),
			array_slice(func_get_args(), 1)
		);
	}

	public function testBbcodeTagsCanUseAColonFollowedByDigitsAsASuffixToControlHowStartTagsAndEndTagsArePaired()
	{
		$this->cb->BBCodes->addBBCode(
			'B',
			array(
				'nestingLimit' => 1,
				'template' => '<b><xsl:apply-templates /></b>'
			)
		);

		$this->assertTransformation(
			'[B:123]bold tags: [B]text[/B][/B:123]',
			'<rt><B><st>[B:123]</st>bold tags: [B]text[/B]<et>[/B:123]</et></B></rt>',
			'<b>bold tags: [B]text[/B]</b>'
		);
	}
}