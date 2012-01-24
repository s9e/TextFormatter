<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\Plugins\EscaperParser
*/
class EscaperParserTest extends Test
{
	/**
	* @test
	*/
	public function Can_escape_an_ASCII_character()
	{
		$this->cb->loadPlugin('Escaper');

		$this->assertTransformation(
			'x\\yz',
			'<rt>x<ESC>\\y</ESC>z</rt>',
			'xyz'
		);
	}

	/**
	* @test
	*/
	public function Can_escape_a_LF()
	{
		$this->cb->loadPlugin('Escaper');

		$this->assertTransformation(
			"x\\\nz",
			"<rt>x<ESC>\\\n</ESC>z</rt>",
			"x\nz"
		);
	}

	/**
	* @test
	*/
	public function Can_escape_a_Unicode_character()
	{
		$this->cb->loadPlugin('Escaper');

		$this->assertTransformation(
			'x\\♥z',
			'<rt>x<ESC>\\♥</ESC>z</rt>',
			'x♥z'
		);
	}

	/**
	* @test
	*/
	public function Can_escape_a_backslash()
	{
		$this->cb->loadPlugin('Escaper');

		$this->assertTransformation(
			'x\\\\z',
			'<rt>x<ESC>\\\\</ESC>z</rt>',
			'x\\z'
		);
	}

	/**
	* @test
	*/
	public function A_backslash_at_the_end_of_the_text_does_is_treated_like_normal_text()
	{
		$this->cb->loadPlugin('Escaper');

		$this->assertTransformation(
			'x\\',
			'<pt>x\\</pt>',
			'x\\'
		);
	}

	/**
	* @test
	*/
	public function Can_use_a_custom_tagName()
	{
		$this->cb->loadPlugin('Escaper', null, array('tagName' => 'X'));

		$this->assertTransformation(
			'x\\yz',
			'<rt>x<X>\\y</X>z</rt>',
			'xyz'
		);
	}
}