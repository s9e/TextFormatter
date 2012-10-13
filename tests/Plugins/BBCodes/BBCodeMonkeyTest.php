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
		echo BBCodeMonkey::replaceTokens('<b>{NUMBER1}</b>', array('NUMBER1'=>'width'), null);
		BBCodeMonkey::parse('[flash={NUMBER1},{NUMBER2} width={NUMBER1} height={NUMBER2}]{URL}[/flash]');
//		BBCodeMonkey::parse('[flash={PARSE=/^(?<width>\\d+),(?<height>\\d+)$/}]{URL}[/flash]');
//		BBCodeMonkey::parse('[flash={NUMBER1},{NUMBER2} flash={NUMBER2}-{NUMBER1}]{URL}[/flash]');
//		BBCodeMonkey::parse('[youtube]{PARSE=#http://foo?id=(?<id>\\w+)#}[/youtube]');
//		BBCodeMonkey::parse('[flash={NUMBER1},{NUMBER2} foo={INT}]{URL}[/flash]');
	}
}