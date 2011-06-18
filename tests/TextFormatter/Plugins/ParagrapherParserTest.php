<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\ParagrapherParser
*/
class ParagrapherParserTest extends Test
{
	public function testATextWithNoLinebreaksIsRenderedAsASingleParagraph()
	{
		$this->cb->loadPlugin('Paragrapher');

		$this->assertTransformation(
			'Y helo thar',
			'<rt><P>Y helo thar</P></rt>',
			'<p>Y helo thar</p>'
		);
	}

	public function testConsecutiveLinebreaksGenerateOneParagraph()
	{
		$this->cb->loadPlugin('Paragrapher');

		$this->assertTransformation(
			"Y helo thar\n\nGood day, sir",
			"<rt><P>Y helo thar</P><P><i>\n\n</i>Good day, sir</P></rt>",
			'<p>Y helo thar</p><p>Good day, sir</p>'
		);
	}

	/**
	* @depends testATextWithNoLinebreaksIsRenderedAsASingleParagraph
	*/
	public function testWhitespaceAtTheBeginningOfAParagraphIsRemoved()
	{
		$this->cb->loadPlugin('Paragrapher');

		$this->assertTransformation(
			"\n \n Y helo thar",
			"<rt><P><i>\n \n </i>Y helo thar</P></rt>",
			'<p>Y helo thar</p>'
		);
	}

	/**
	* @depends testATextWithNoLinebreaksIsRenderedAsASingleParagraph
	*/
	public function testWhitespaceAtTheEndOfAParagraphIsRemoved()
	{
		$this->cb->loadPlugin('Paragrapher');

		$this->assertTransformation(
			"Y helo thar   ",
			"<rt><P>Y helo thar<i>   </i></P></rt>",
			'<p>Y helo thar</p>'
		);
	}

	/**
	* @testdox A single linefeed is rendered as a <br> tag
	*/
	public function testSingleLF()
	{
		$this->cb->loadPlugin('Paragrapher');

		$this->assertTransformation(
			"Y helo thar\nOh, hai",
			"<rt><P>Y helo thar<BR/><i>\n</i>Oh, hai</P></rt>",
			'<p>Y helo thar<br>Oh, hai</p>'
		);
	}

	/**
	* @testdox Two consecutive linefeeds are rendered as a new paragraph
	*/
	public function testTwoLF()
	{
		$this->cb->loadPlugin('Paragrapher');

		$this->assertTransformation(
			"Y helo thar\n\nOh, hai",
			"<rt><P>Y helo thar</P><P><i>\n\n</i>Oh, hai</P></rt>",
			'<p>Y helo thar</p><p>Oh, hai</p>'
		);
	}

	/**
	* @testdox Can use a custom tag name for paragraphs
	* @depends testATextWithNoLinebreaksIsRenderedAsASingleParagraph
	*/
	public function testCustomParagraphTagName()
	{
		$this->cb->loadPlugin('Paragrapher', null, array('paragraphTagName' => 'PARA'));

		$this->assertTransformation(
			"Y helo thar",
			"<rt><PARA>Y helo thar</PARA></rt>",
			'<p>Y helo thar</p>'
		);
	}

	/**
	* @testdox Can use a custom tag name for linebreaks
	* @depends testSingleLF
	*/
	public function testCustomLinebreakTagName()
	{
		$this->cb->loadPlugin('Paragrapher', null, array('linebreakTagName' => 'LB'));

		$this->assertTransformation(
			"Y helo thar\nOh, hai",
			"<rt><P>Y helo thar<LB/><i>\n</i>Oh, hai</P></rt>",
			'<p>Y helo thar<br>Oh, hai</p>'
		);
	}
}