<?php

namespace s9e\Toolkit\TextFormatter\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class ParserTest extends \PHPUnit_Framework_TestCase
{
	public function testCloseParentRuleWithRtrimContent()
	{
		$cb = new ConfigBuilder;
		$cb->addPredefinedBBCode('LIST');

		$text =
'[LIST]
	[*]one
	[*]two
[/LIST]';

		$expected =
'<rt><LIST style="disc"><st>[LIST]</st><i>
	</i><LI><st>[*]</st>one<i>
	</i></LI><LI><st>[*]</st>two<i>
</i></LI><et>[/LIST]</et></LIST></rt>';

		$actual = $cb->getParser()->parse($text);

		$this->assertXmlStringEqualsXmlString($expected, $actual);
		$this->assertSame($expected, $actual);
	}
}