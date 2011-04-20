<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\EmoticonsConfig
*/
class EmoticonsConfigTest extends Test
{
	public function testReturnsFalseIfNoEmoticonsAreAdded()
	{
		$this->assertFalse($this->cb->Emoticons->getConfig());
	}

	public function testTagNameCanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('Emoticons', null, array('tagName' => 'EMOTICON'));
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');

		$this->assertArrayMatches(
			array('tagName' => 'EMOTICON'),
			$this->cb->Emoticons->getConfig()
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