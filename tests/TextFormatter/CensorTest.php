<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* covers s9e\Toolkit\TextFormatter\Plugins\CensorConfig
* covers s9e\Toolkit\TextFormatter\Plugins\CensorParser
*/
class CensorTest extends Test
{
	public function testCensorPluginIsOptimizedAwayIfNoWordsAreAdded()
	{
		$this->cb->loadPlugin('Censor');

		$this->assertArrayNotHasKey(
			'Censor',
			$this->cb->getPluginsConfig()
		);
	}

	public function testCensorWithDefaultReplacement()
	{
		$this->cb->Censor->addWord('apple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ****'
		);
	}

	/**
	* @depends testCensorWithDefaultReplacement
	*/
	public function testDefaultReplacementCanBeReplacedAtLoadingTime()
	{
		$this->cb->loadPlugin('Censor', null, array('defaultReplacement' => '####'));
		$this->cb->Censor->addWord('apple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C>apple</C></rt>',
			'You dirty ####'
		);
	}

	/**
	* @depends testCensorWithDefaultReplacement
	*/
	public function testTagNameCanBeReplacedAtLoadingTime()
	{
		$this->cb->loadPlugin('Censor', null, array('tagName' => 'censored'));
		$this->cb->Censor->addWord('apple');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <CENSORED>apple</CENSORED></rt>',
			'You dirty ****'
		);
	}

	public function testCensorWithCustomReplacement()
	{
		$this->cb->Censor->addWord('apple', 'orange');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C with="orange">apple</C></rt>',
			'You dirty orange'
		);
	}

	/**
	* @depends testCensorWithCustomReplacement
	*/
	public function testAttributeNameCanBeReplacedAtLoadingTime()
	{
		$this->cb->loadPlugin('Censor', null, array('attrName' => 'replacement'));
		$this->cb->Censor->addWord('apple', 'orange');

		$this->assertTransformation(
			'You dirty apple',
			'<rt>You dirty <C replacement="orange">apple</C></rt>',
			'You dirty orange'
		);
	}
}