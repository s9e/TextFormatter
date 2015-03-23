<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Utils;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Utils
*/
class UtilsTest extends Test
{
	/**
	* @testdox removeFormatting() tests
	* @dataProvider getRemoveFormattingTests
	*/
	public function testRemoveFormatting($original, $expected)
	{
		$this->assertSame($expected, Utils::removeFormatting($original));
	}

	public function getRemoveFormattingTests()
	{
		return [
			[
				'<t>Plain text</t>',
				'Plain text'
			],
			[
				'<t>&lt;Plain text&gt;</t>',
				'<Plain text>'
			],
			[
				"<t>a<br/>\nb</t>",
				"a\nb"
			],
			[
				'<r><B><s>[b]</s>Rich<e>[/b]</e></B> text <E>:)</E></r>',
				'Rich text :)'
			],
		];
	}
}