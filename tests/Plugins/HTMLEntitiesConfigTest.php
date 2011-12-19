<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\TextFormatter\Plugins\HTMLEntitiesConfig
*/
class HTMLEntitiesConfigTest extends Test
{
	/**
	* @test
	*/
	public function tagName_can_be_customized_at_loading_time()
	{
		$this->cb->loadPlugin('HTMLEntities', null, array('tagName' => 'XYZ'));

		$this->assertArrayMatches(
			array('tagName' => 'XYZ'),
			$this->cb->HTMLEntities->getConfig()
		);
	}

	/**
	* @test
	*/
	public function attrName_can_be_customized_at_loading_time()
	{
		$this->cb->loadPlugin('HTMLEntities', null, array('attrName' => 'xyz'));

		$this->assertArrayMatches(
			array('attrName' => 'xyz'),
			$this->cb->HTMLEntities->getConfig()
		);
	}

	public function testDoesNotAttemptToCreateItsTagIfItAlreadyExists()
	{
		$this->cb->loadPlugin('HTMLEntities');
		unset($this->cb->HTMLEntities);
		$this->cb->loadPlugin('HTMLEntities');
	}

	/**
	* @test
	*/
	public function Individual_HTML_entities_can_be_disabled_via_disableEntity()
	{
		$this->cb->HTMLEntities->disableEntity('&amp;');
		$this->cb->HTMLEntities->disableEntity('&lt;');

		$this->assertArrayMatches(
			array('disabled' => array('&amp;' => 1, '&lt;' => 1)),
			$this->cb->HTMLEntities->getConfig()
		);
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid HTML entity 'amp;'
	*/
	public function disableEntity_throws_an_exception_on_invalid_entities()
	{
		$this->cb->HTMLEntities->disableEntity('amp;');
	}

	/**
	* @test
	* @testdox getJSParser() returns the source of its Javascript parser
	*/
	public function getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../src/Plugins/HTMLEntitiesParser.js',
			$this->cb->HTMLEntities->getJSParser()
		);
	}

	/**
	* @test
	*/
	public function Disabled_entities_are_preserved_in_Javascript_config()
	{
		$this->cb->HTMLEntities->disableEntity('&hearts;');

		$this->assertArrayMatches(
			array('preserveKeys' => array(array('disabled', true))),
			$this->cb->HTMLEntities->getJSConfigMeta()
		);
	}
}