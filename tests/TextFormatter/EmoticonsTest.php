<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* covers s9e\Toolkit\TextFormatter\Plugins\EmoticonsConfig
* covers s9e\Toolkit\TextFormatter\Plugins\EmoticonsParser
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

	public function testEmoticonsXslIsAutomaticallyUpdatedWhenEmoticonsAreAdded()
	{
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');
		$this->assertContains(':)', $this->cb->getXSL());
		$this->assertNotContains(':lol:', $this->cb->getXSL());

		$this->cb->Emoticons->addEmoticon(':lol:', '<img src="lol.png" />');
		$this->assertContains(':lol:', $this->cb->getXSL());
	}

	/**
	* @depends testEmoticonsXslIsAutomaticallyUpdatedWhenEmoticonsAreAdded
	*/
	public function testXslAutoUpdateCanBeDisabledByCallingTheDisableAutoUpdateMethod()
	{
		$this->cb->Emoticons->disableAutoUpdate();

		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');
		$this->assertNotContains(':)', $this->cb->getXSL());
	}

	/**
	* @depends testXslAutoUpdateCanBeDisabledByCallingTheDisableAutoUpdateMethod
	*/
	public function testXslAutoUpdateCanBeReenabledByCallingTheEnableAutoUpdateMethod()
	{
		$this->cb->Emoticons->disableAutoUpdate();
		$this->cb->Emoticons->enableAutoUpdate();

		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');
		$this->assertContains(':)', $this->cb->getXSL());
	}

	/**
	* @depends testXslAutoUpdateCanBeDisabledByCallingTheDisableAutoUpdateMethod
	*/
	public function testXslUpdateCanBeManuallyTriggeredByCallingTheUpdateXslMethod()
	{
		$this->cb->Emoticons->disableAutoUpdate();
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');
		$this->cb->Emoticons->updateXSL();
		$this->assertContains(':)', $this->cb->getXSL());
	}
}