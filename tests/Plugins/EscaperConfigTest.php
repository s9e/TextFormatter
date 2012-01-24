<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\Plugins\EscaperConfig
*/
class EscaperConfigTest extends Test
{
	/**
	* @test
	*/
	public function Creates_an_ESC_tag_by_default()
	{
		$this->cb->loadPlugin('Escaper');

		$this->assertTrue($this->cb->tagExists('ESC'));
	}

	/**
	* @test
	*/
	public function tagName_can_be_customized_at_loading_time()
	{
		$this->cb->loadPlugin('Escaper', null, array('tagName' => 'X'));

		$this->assertArrayMatches(
			array('tagName' => 'X'),
			$this->cb->Escaper->getConfig()
		);
	}

	/**
	* @test
	*/
	public function Generates_a_regexp()
	{
		$this->assertArrayHasKey('regexp', $this->cb->Escaper->getConfig());
	}

	/**
	* @test
	* @testdox getJSParser() returns the source of its Javascript parser
	*/
	public function getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../src/Plugins/EscaperParser.js',
			$this->cb->Escaper->getJSParser()
		);
	}
}