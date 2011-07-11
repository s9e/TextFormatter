<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\TextFormatter\Plugins\AutolinkConfig
*/
class AutolinkConfigTest extends Test
{
	/**
	* @test
	*/
	public function Automatically_creates_an_URL_tag()
	{
		$this->cb->loadPlugin('Autolink');
		$this->assertTrue($this->cb->tagExists('URL'));
	}

	/**
	* @test
	*/
	public function Generates_a_regexp()
	{
		$this->assertArrayHasKey('regexp', $this->cb->Autolink->getConfig());
	}

	/**
	* @test
	*/
	public function getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../src/Plugins/AutolinkParser.js',
			$this->cb->Autolink->getJSParser()
		);
	}

	/**
	* @test
	*/
	public function getJSConfig_removes_the_possessive_quantifier_from_the_regexp()
	{
		$this->assertNotContains(
			'++',
			$this->cb->Autolink->getJSConfig()
		);
	}
}