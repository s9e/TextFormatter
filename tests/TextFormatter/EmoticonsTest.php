<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* covers s9e\Toolkit\TextFormatter\Plugins\CensorConfig
* covers s9e\Toolkit\TextFormatter\Plugins\CensorParser
*/
class EmoticonsTest extends Test
{
	public function testEmoticonsPluginIsOptimizedAwayIfNoEmoticonsAreAdded()
	{
		$this->cb->loadPlugin('Emoticons');

		$this->assertArrayNotHasKey(
			'Emoticons',
			$this->cb->getPluginsConfig()
		);
	}

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

	/**
	* @depends testAnEmoticonCanBeReplacedByAnImgTag
	*/
	public function testTagNameCanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('Emoticons', null, array('tagName' => 'emoticon'));
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');

		$this->assertTransformation(
			'Hello :)',
			'<rt>Hello <EMOTICON>:)</EMOTICON></rt>',
			'Hello <img src="smiley.png">'
		);
	}
}