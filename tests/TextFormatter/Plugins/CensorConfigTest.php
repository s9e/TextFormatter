<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\CensorConfig
*/
class CensorConfigTest extends Test
{
	/**
	* @test
	*/
	public function getConfig_returns_false_if_no_words_were_added()
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

	/**
	* @test
	*/
	public function getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../../src/TextFormatter/Plugins/CensorParser.js',
			$this->cb->Censor->getJSParser()
		);
	}

	/**
	* @test
	*/
	public function getJSConfig_returns_the_replacements_as_pairs_in_a_numerically_indexed_array()
	{
		$this->cb->Censor->addWord('foo', 'bar');

		$this->assertArrayMatches(
			array(
				'replacements' => array(
					array('#^foo$#iDu', 'bar')
				)
			),
			$this->cb->Censor->getJSConfig()
		);
	}
}