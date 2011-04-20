<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\CensorConfig
*/
class CensorConfigTest extends Test
{
	public function testGetConfigReturnsFalseIfNoWordsAreAdded()
	{
		$this->assertFalse($this->cb->loadPlugin('Censor')->getConfig());
	}

	public function testGeneratesARegexp()
	{
		$this->cb->Censor->addWord('apple');

		$this->assertArrayMatches(
			array('regexp' => '#\\bapple\\b#iu'),
			$this->cb->Censor->getConfig()
		);
	}

	public function testAcceptCustomReplacementForSpecificWords()
	{
		$this->cb->Censor->addWord('apple', 'banana');

		$this->assertArrayMatches(
			array(
				'replacements' => array('#^apple$#iDu' => 'banana')
			),
			$this->cb->Censor->getConfig()
		);
	}

	public function testDefaultReplacementCanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('Censor', null, array('defaultReplacement' => '####'));

		$this->assertContains(
			'####',
			$this->cb->getXSL()
		);
	}

	public function testTagNameCanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('Censor', null, array('tagName' => 'CENSORED'));
		$this->cb->Censor->addWord('apple');

		$this->assertArrayMatches(
			array('tagName' => 'CENSORED'),
			$this->cb->Censor->getConfig()
		);
	}

	public function testAttributeNameCanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('Censor', null, array('attrName' => 'replacement'));
		$this->cb->Censor->addWord('apple');

		$this->assertArrayMatches(
			array('attrName' => 'replacement'),
			$this->cb->Censor->getConfig()
		);
	}

	public function testDoesNotAttemptToCreateItsTagIfItAlreadyExists()
	{
		$this->cb->loadPlugin('Censor');
		unset($this->cb->Censor);
		$this->cb->loadPlugin('Censor');
	}
}