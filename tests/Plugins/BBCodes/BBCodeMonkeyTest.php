<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Plugins\BBCodes;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\BBCodes\BBCodeMonkey;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\BBCodeMonkey
*/
class BBCodeMonkeyTest extends Test
{
	/**
	* @testdox Attributes from attribute preprocessors are automatically created using their subpattern as filtering regexp
	*/
	public function testAttributesFromAttributePreprocessors()
	{
		BBCodeMonkey::parse('[flash={PARSE=/^(?<width>\\d+),(?<height>\\d+)$/}]{URL}[/flash]');
//		BBCodeMonkey::parse('[flash={NUMBER},{NUMBER}]{URL}[/flash]');
//		BBCodeMonkey::parse('[youtube]{PARSE=#http://foo?id=(?<id>\\w+)#}[/youtube]');
//		BBCodeMonkey::parse('[flash={NUMBER1},{NUMBER2} foo={INT}]{URL}[/flash]');
	}
}