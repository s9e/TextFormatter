<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\WittyPantsConfig
*/
class WittyPantsConfigTest extends Test
{
	public function testTagNameCanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('WittyPants', null, array('tagName' => 'XYZ'));

		$this->assertArrayMatches(
			array('tagName' => 'XYZ'),
			$this->cb->WittyPants->getConfig()
		);
	}

	public function testAttributeNameCanBeCustomizedAtLoadingTime()
	{
		$this->cb->loadPlugin('WittyPants', null, array('attrName' => 'xyz'));

		$this->assertArrayMatches(
			array('attrName' => 'xyz'),
			$this->cb->WittyPants->getConfig()
		);
	}

	public function testDoesNotAttemptToCreateItsTagIfItAlreadyExists()
	{
		$this->cb->loadPlugin('WittyPants');
		unset($this->cb->WittyPants);
		$this->cb->loadPlugin('WittyPants');
	}

	/**
	* @test
	*/
	public function getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../../src/TextFormatter/Plugins/WittyPantsParser.js',
			$this->cb->WittyPants->getJSParser()
		);
	}
}