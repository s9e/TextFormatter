<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Unparser;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Unparser
*/
class UnparserTest extends Test
{
	/**
	* @testdox unparse('<pt>Plain text</pt>') returns 'Plain text'
	*/
	public function testUnparsePlainText()
	{
		$this->assertSame(
			'Plain text',
			Unparser::unparse('<pt>Plain text</pt>')
		);
	}

	/**
	* @testdox stripFormatting('<pt>Plain text</pt>') returns 'Plain text'
	*/
	public function testPlainTextPlainText()
	{
		$this->assertSame(
			'Plain text',
			Unparser::stripFormatting('<pt>Plain text</pt>')
		);
	}

	/**
	* @testdox unparse('<pt>&lt;Plain text&gt;</pt>') returns '<Plain text>'
	*/
	public function testUnparsePlainTextWithSpecialChars()
	{
		$this->assertSame(
			'<Plain text>',
			Unparser::unparse('<pt>&lt;Plain text&gt;</pt>')
		);
	}

	/**
	* @testdox stripFormatting('<pt>&lt;Plain text&gt;</pt>') returns '<Plain text>'
	*/
	public function testPlainTextPlainTextWithSpecialChars()
	{
		$this->assertSame(
			'<Plain text>',
			Unparser::stripFormatting('<pt>&lt;Plain text&gt;</pt>')
		);
	}

	/**
	* @testdox unparse("<mt>a<br />\nb</mt>") returns "a\nb"
	*/
	public function testUnparsePlainTextWithLinebreaks()
	{
		$this->assertSame(
			"a\nb",
			Unparser::unparse("<mt>a<br />\nb</mt>")
		);
	}

	/**
	* @testdox stripFormatting("<mt>a<br />\nb</mt>") returns "a\nb"
	*/
	public function testPlainTextPlainTextWithLinebreaks()
	{
		$this->assertSame(
			"a\nb",
			Unparser::stripFormatting("<mt>a<br />\nb</mt>")
		);
	}

	/**
	* @testdox unparse('<rt><B><st>[b]</st>Rich<et>[/b]</et></B> text <E>:)</E></rt>') returns '[b]Rich[/b] text :)'
	*/
	public function testUnparseRichText()
	{
		$this->assertSame(
			'[b]Rich[/b] text :)',
			Unparser::unparse('<rt><B><st>[b]</st>Rich<et>[/b]</et></B> text <E>:)</E></rt>')
		);
	}

	/**
	* @testdox stripFormatting('<rt><B><st>[b]</st>Rich<et>[/b]</et></B> text <E>:)</E></rt>') returns 'Rich text :)'
	*/
	public function testPlainTextRichText()
	{
		$this->assertSame(
			'Rich text :)',
			Unparser::stripFormatting('<rt><B><st>[b]</st>Rich<et>[/b]</et></B> text <E>:)</E></rt>')
		);
	}

	/**
	* @testdox Can unparse representations that were over-escaped
	*/
	public function testExoticIR()
	{
		$this->assertSame(
			'&<>"\'',
			Unparser::unparse('<pt>&amp;&lt;&gt;&quot;&#039;</pt>')
		);
	}
}