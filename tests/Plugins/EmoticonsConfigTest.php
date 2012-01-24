<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\Plugins\EmoticonsConfig
*/
class EmoticonsConfigTest extends Test
{
	/**
	* @test
	*/
	public function tagName_can_be_customized_at_loading_time()
	{
		$this->cb->loadPlugin('Emoticons', null, array('tagName' => 'EMOTICON'));
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');

		$this->assertArrayMatches(
			array('tagName' => 'EMOTICON'),
			$this->cb->Emoticons->getConfig()
		);
	}

	/**
	* @testdox getConfig() returns false if no emoticons were added
	*/
	public function test_getConfig_returns_false_if_no_emoticons_were_added()
	{
		$this->assertFalse($this->cb->Emoticons->getConfig());
	}

	public function testEmoticonsTemplateIsAutomaticallyUpdatedWhenEmoticonsAreAdded()
	{
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smiley.png" />');
		$this->assertContains(':)', $this->cb->getXSL());
		$this->assertNotContains(':lol:', $this->cb->getXSL());

		$this->cb->Emoticons->addEmoticon(':lol:', '<img src="lol.png" />');
		$this->assertContains(':lol:', $this->cb->getXSL());
	}

	/**
	* @testdox A single emoticon can be created with addEmoticon()
	*/
	public function testSingle()
	{
		$this->cb->Emoticons->addEmoticon(':e1:', '<img src="e1.png" />');

		$this->assertContains(':e1:', $this->cb->getXSL());
	}

	/**
	* @testdox Multiple emoticons can be created at once with addEmoticons()
	*/
	public function testMultiple()
	{
		$this->cb->Emoticons->addEmoticons(array(
			':e1:' => '<img src="e1.png" />',
			':e2:' => '<img src="e2.png" />'
		));

		$this->assertContains(':e1:', $this->cb->getXSL());
		$this->assertContains(':e2:', $this->cb->getXSL());
	}

	/**
	* @testdox getJSParser() returns the source of its Javascript parser
	*/
	public function test_getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../src/Plugins/EmoticonsParser.js',
			$this->cb->Emoticons->getJSParser()
		);
	}

	/**
	* @testdox Emoticons code can contain single quotes
	*/
	public function testSingleQuotes()
	{
		$this->cb->Emoticons->addEmoticon(":')", '<img src="smiley.png" />');

		$this->assertTransformation(
			"hey :')",
			"<rt>hey <E>:')</E></rt>",
			'hey <img src="smiley.png">'
		);
	}

	/**
	* @testdox Emoticons code can contain double quotes
	*/
	public function testDoubleQuotes()
	{
		$this->cb->Emoticons->addEmoticon(':")', '<img src="smiley.png" />');

		$this->assertTransformation(
			'hey :")',
			'<rt>hey <E>:")</E></rt>',
			'hey <img src="smiley.png">'
		);
	}

	/**
	* @testdox Emoticons code can contain single and double quotes at the same time
	*/
	public function testBothQuotes()
	{
		$this->cb->Emoticons->addEmoticon(':\'")', '<img src="smiley.png" />');

		$this->assertTransformation(
			'hey :\'")',
			'<rt>hey <E>:\'")</E></rt>',
			'hey <img src="smiley.png">'
		);
	}
}