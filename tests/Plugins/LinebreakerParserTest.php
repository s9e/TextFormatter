<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\Plugins\LinebreakerParser
*/
class LinebreakerParserTest extends Test
{
	public function setUp()
	{
		$this->cb->loadPlugin('Linebreaker');
	}

	/**
	* @test
	*/
	public function LF_characters_are_replaced_with_a_BR_tag()
	{
		$this->assertTransformation(
			"One\nTwo",
			"<rt>One<BR>\n</BR>Two</rt>",
			'One<br>Two'
		);
	}

	/**
	* @test
	*/
	public function CRLF_character_pairs_are_replaced_with_a_BR_tag()
	{
		$this->assertTransformation(
			"One\r\nTwo",
			"<rt>One<BR>&#xD;\n</BR>Two</rt>",
			'One<br>Two'
		);
	}
}