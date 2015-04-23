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
	* @testdox unparse('<t>Plain text</t>') returns 'Plain text'
	*/
	public function testUnparsePlainText()
	{
		$this->assertSame(
			'Plain text',
			Unparser::unparse('<t>Plain text</t>')
		);
	}

	/**
	* @testdox unparse('<t>&lt;Plain text&gt;</t>') returns '<Plain text>'
	*/
	public function testUnparsePlainTextWithSpecialChars()
	{
		$this->assertSame(
			'<Plain text>',
			Unparser::unparse('<t>&lt;Plain text&gt;</t>')
		);
	}

	/**
	* @testdox unparse("<mt>a<br />\nb</mt>") returns "a\nb"
	*/
	public function testUnparsePlainTextWithLinebreaks()
	{
		$this->assertSame(
			"a\nb",
			Unparser::unparse("<t>a<br/>\nb</t>")
		);
	}

	/**
	* @testdox unparse('<r><B><s>[b]</s>Rich<e>[/b]</e></B> text <E>:)</E></r>') returns '[b]Rich[/b] text :)'
	*/
	public function testUnparseRichText()
	{
		$this->assertSame(
			'[b]Rich[/b] text :)',
			Unparser::unparse('<r><B><s>[b]</s>Rich<e>[/b]</e></B> text <E>:)</E></r>')
		);
	}

	/**
	* @testdox Can unparse representations that were over-escaped
	*/
	public function testExoticIR()
	{
		$this->assertSame(
			'&<>"\'',
			Unparser::unparse('<t>&amp;&lt;&gt;&quot;&#039;</t>')
		);
	}

	/**
	* @testdox Characters outside Unicode's BMP are decoded
	*/
	public function testUnicodeSMP()
	{
		$this->assertSame(
			'😀',
			Unparser::unparse('<t>&#128512;</t>')
		);
	}
}