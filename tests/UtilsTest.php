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
	* @testdox removeFormatting('<t>Plain text</t>') returns 'Plain text'
	*/
	public function testRemoveFormattingPlainText()
	{
		$this->assertSame(
			'Plain text',
			Utils::removeFormatting('<t>Plain text</t>')
		);
	}

	/**
	* @testdox removeFormatting('<t>&lt;Plain text&gt;</t>') returns '<Plain text>'
	*/
	public function testRemoveFormattingPlainTextWithSpecialChars()
	{
		$this->assertSame(
			'<Plain text>',
			Utils::removeFormatting('<t>&lt;Plain text&gt;</t>')
		);
	}

	/**
	* @testdox removeFormatting("<mt>a<br />\nb</mt>") returns "a\nb"
	*/
	public function testRemoveFormattingPlainTextWithLinebreaks()
	{
		$this->assertSame(
			"a\nb",
			Utils::removeFormatting("<t>a<br/>\nb</t>")
		);
	}

	/**
	* @testdox removeFormatting('<r><B><s>[b]</s>Rich<e>[/b]</e></B> text <E>:)</E></r>') returns 'Rich text :)'
	*/
	public function testRemoveFormattingRichText()
	{
		$this->assertSame(
			'Rich text :)',
			Utils::removeFormatting('<r><B><s>[b]</s>Rich<e>[/b]</e></B> text <E>:)</E></r>')
		);
	}
}