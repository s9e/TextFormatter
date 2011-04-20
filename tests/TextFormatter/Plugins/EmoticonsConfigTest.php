<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\EmoticonsConfig
*/
class EmoticonsConfigTest extends Test
{
	/**
	* @test
	*/
	public function getConfig_returns_false_if_no_emoticons_were_added()
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
	* @test
	* @depends testEmoticonsXslIsAutomaticallyUpdatedWhenEmoticonsAreAdded
	*/
	public function Xsl_auto_update_can_be_disabled_by_calling_disableAutoUpdate()
	{
		$this->cb->Emoticons->disableAutoUpdate();

		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');
		$this->assertNotContains(':)', $this->cb->getXSL());
	}

	/**
	* @test
	* @depends Xsl_auto_update_can_be_disabled_by_calling_disableAutoUpdate
	*/
	public function Xsl_auto_update_can_be_reenabled_by_calling_enableAutoUpdate()
	{
		$this->cb->Emoticons->disableAutoUpdate();
		$this->cb->Emoticons->enableAutoUpdate();

		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');
		$this->assertContains(':)', $this->cb->getXSL());
	}

	/**
	* @test
	* @depends Xsl_auto_update_can_be_disabled_by_calling_disableAutoUpdate
	*/
	public function Xsl_update_can_be_manually_triggered_by_calling_updateXSL()
	{
		$this->cb->Emoticons->disableAutoUpdate();
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');
		$this->cb->Emoticons->updateXSL();
		$this->assertContains(':)', $this->cb->getXSL());
	}
}