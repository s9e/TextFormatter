<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\Plugins\LinebreakerConfig
*/
class LinebreakerConfigTest extends Test
{
	/**
	* @test
	*/
	public function Automatically_creates_a_BR_tag()
	{
		$this->cb->loadPlugin('Linebreaker');
		$this->assertTrue($this->cb->tagExists('BR'));
	}

	/**
	* @depends Automatically_creates_a_BR_tag
	*/
	public function testDoesNotAttemptToCreateItsTagIfItAlreadyExists()
	{
		$this->cb->loadPlugin('Linebreaker');
		unset($this->cb->Linebreaker);
		$this->cb->loadPlugin('Linebreaker');
	}

	/**
	* @test
	*/
	public function Generates_a_regexp()
	{
		$this->assertArrayHasKey('regexp', $this->cb->Linebreaker->getConfig());
	}

	/**
	* @test
	* @testdox getJSParser() returns the source of its Javascript parser
	*/
	public function getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../src/Plugins/LinebreakerParser.js',
			$this->cb->Linebreaker->getJSParser()
		);
	}
}