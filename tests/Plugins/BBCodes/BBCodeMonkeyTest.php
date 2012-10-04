<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Plugins\BBCodes;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\BBCodes\BBCodeMonkey;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\BBCodeMonkey
*/
class BBCodeMonkeyTest extends Test
{
	public function test()
	{
		BBCodeMonkey::parse('[youtube={PARSE=#http://foo?id=(?<id>\\w+)#} *={INT} /]');
//		BBCodeMonkey::parse('[youtube]{PARSE=#http://foo?id=(?<id>\\w+)#}[/youtube]');
//		BBCodeMonkey::parse('[flash={NUMBER1},{NUMBER2} foo={INT}]{URL}[/flash]');
	}
}