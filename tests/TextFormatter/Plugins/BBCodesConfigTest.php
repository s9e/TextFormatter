<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\BBCodesConfig
*/
class BBCodesConfigTest extends Test
{
	/**
	* @test
	*/
	public function getConfig_returns_false_if_no_BBCodes_were_added()
	{
		$this->assertFalse($this->cb->BBCodes->getConfig());
	}

	/**
	* @test
	*/
	public function A_single_asterisk_is_accepted_as_a_BBCode_name()
	{
		$this->assertTrue($this->cb->BBCodes->isValidBBCodeName('*'));
	}

	/**
	* @test
	*/
	public function An_asterisk_followed_by_anything_is_rejected_as_a_BBCode_name()
	{
		$this->assertFalse($this->cb->BBCodes->isValidBBCodeName('**'));
		$this->assertFalse($this->cb->BBCodes->isValidBBCodeName('*b'));
	}

	/**
	* @test
	*/
	public function BBCode_names_can_start_with_a_letter()
	{
		$this->assertTrue($this->cb->BBCodes->isValidBBCodeName('a'));
	}

	/**
	* @test
	*/
	public function BBCode_names_cannot_start_with_anything_else()
	{
		$allowedChars    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz*';
		$disallowedChars = count_chars($allowedChars, 4);

		foreach (str_split($disallowedChars, 1) as $c)
		{
			$this->assertFalse($this->cb->BBCodes->isValidBBCodeName($c));
		}
	}

	/**
	* @test
	*/
	public function BBCode_names_can_only_contain_letters_numbers_and_underscores()
	{
		$allowedChars    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_';
		$disallowedChars = count_chars($allowedChars, 4);

		foreach (str_split($disallowedChars, 1) as $c)
		{
			$this->assertFalse($this->cb->BBCodes->isValidBBCodeName('A' . $c));
		}
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid BBCode name ']'
	*/
	public function addBBCode_rejects_invalid_BBCode_names()
	{
		$this->cb->BBCodes->addBBCode(']');
	}

	/**
	* @test
	*/
	public function BBCodes_are_mapped_to_a_tag_of_the_same_name_by_default()
	{
		$this->cb->BBCodes->addBBCode('B');

		$parserConfig = $this->cb->getParserConfig();

		$this->assertArrayHasKey('B', $parserConfig['tags']);
		$this->assertSame(
			'B', $parserConfig['plugins']['BBCodes']['bbcodesConfig']['B']['tagName']
		);
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage BBCode 'A' already exists
	*/
	public function addBBCode_throws_an_exception_if_the_BBCode_name_is_already_in_use()
	{
		$this->cb->BBCodes->addBBCode('A');
		$this->cb->BBCodes->addBBCode('A');
	}

	/**
	* @test
	*/
	public function A_BBCode_can_map_to_a_tag_of_a_different_name()
	{
		$this->cb->BBCodes->addBBCode('A', array('tagName' => 'B'));
		$this->assertTrue($this->cb->tagExists('B'));
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Tag 'A' does not exist
	*/
	public function addBBCodeAlias_throws_an_exception_if_the_tag_does_not_exist()
	{
		$this->cb->BBCodes->addBBCodeAlias('A', 'A');
	}

	/**
	* @test
	* @depend BBCodes_are_mapped_to_a_tag_of_the_same_name_by_default
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage BBCode 'A' already exists
	*/
	public function addBBCodeAlias_throws_an_exception_if_the_BBCode_already_exisst()
	{
		$this->cb->BBCodes->addBBCode('A');
		$this->cb->BBCodes->addBBCodeAlias('A', 'A');
	}

	/**
	* @test
	*/
	public function Can_tell_whether_a_BBCode_exists()
	{
		$this->assertFalse($this->cb->BBCodes->bbcodeExists('A'));
		$this->cb->BBCodes->addBBCode('A');
		$this->assertTrue($this->cb->BBCodes->bbcodeExists('A'));
	}
}