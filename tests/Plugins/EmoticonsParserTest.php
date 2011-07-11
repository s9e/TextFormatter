<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\TextFormatter\Plugins\EmoticonsParser
*/
class EmoticonsParserTest extends Test
{
	public function testAnEmoticonCanBeReplacedByAnImgTag()
	{
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');

		$this->assertTransformation(
			'Hello :)',
			'<rt>Hello <E>:)</E></rt>',
			'Hello <img src="smiley.png">'
		);
	}

	public function testAnEmoticonCanBeReplacedByAnyHtml()
	{
		$this->cb->Emoticons->addEmoticon(':)', '<span class="smiley"></span>');

		$this->assertTransformation(
			'Hello :)',
			'<rt>Hello <E>:)</E></rt>',
			'Hello <span class="smiley"></span>'
		);
	}

	public function testEmoticonsAreReplacedEverywhereInTheText()
	{
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');

		$this->assertTransformation(
			'Hello :):):)text:):)',
			'<rt>Hello <E>:)</E><E>:)</E><E>:)</E>text<E>:)</E><E>:)</E></rt>',
			'Hello <img src="smiley.png"><img src="smiley.png"><img src="smiley.png">text<img src="smiley.png"><img src="smiley.png">'
		);
	}

	/**
	* @test
	* @depends testAnEmoticonCanBeReplacedByAnImgTag
	*/
	public function Can_use_a_custom_tagName()
	{
		$this->cb->loadPlugin('Emoticons', null, array('tagName' => 'EMOTICON'));
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');

		$this->assertTransformation(
			'Hello :)',
			'<rt>Hello <EMOTICON>:)</EMOTICON></rt>',
			'Hello <img src="smiley.png">'
		);
	}
}