<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\Parser;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\BBCodesParser
*/
class BBCodesParserTest extends Test
{
	/**
	* @test
	*/
	public function Simple_BBcodes_are_parsed()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[B]bold[/B]',
			'<rt><B><st>[B]</st>bold<et>[/B]</et></B></rt>'
		);
	}

	/**
	* @test
	* @depends Simple_BBcodes_are_parsed
	*/
	public function BBCode_names_are_case_insensitive()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[b]bold[/B]',
			'<rt><B><st>[b]</st>bold<et>[/B]</et></B></rt>'
		);
	}

	/**
	* @test
	* @depends Simple_BBcodes_are_parsed
	*/
	public function BBCodes_removed_from_the_config_are_ignored()
	{
		$this->cb->BBCodes->addBBCode('B');

		$parserConfig = $this->cb->getParserConfig();
		unset($parserConfig['plugins']['BBCodes']['bbcodesConfig']['B']);

		$this->parser = new Parser($parserConfig);

		$this->assertParsing(
			'[B]bold[/B]',
			'<pt>[B]bold[/B]</pt>'
		);
	}

	public function testOverlappingTagsAreSortedOut()
	{
		$this->cb->BBCodes->addBBCode(
			'x',
			array('attrs' => array('foo' => array('type' => 'text')))
		);

		$this->assertParsing(
			'[x foo="[b]bar[/b]" /]',
			'<rt><X foo="[b]bar[/b]">[x foo=&quot;[b]bar[/b]&quot; /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function BBCode_tags_can_use_a_colon_followed_by_digits_as_a_suffix_to_control_how_start_tags_and_end_tags_are_paired()
	{
		$this->cb->BBCodes->addBBCode('B', array('nestingLimit' => 1));

		$this->assertParsing(
			'[B:123]bold tags: [B]text[/B][/B:123]',
			'<rt><B><st>[B:123]</st>bold tags: [B]text[/B]<et>[/B:123]</et></B></rt>'
		);
	}

	/**
	* @test
	* @depends Simple_BBcodes_are_parsed
	*/
	public function BBCode_tags_can_be_used_as_singletons_like_self_closing_XML_tags()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[B/]',
			'<rt><B>[B/]</B></rt>'
		);
	}

	/**
	* @test
	* @depends BBCode_tags_can_be_used_as_singletons_like_self_closing_XML_tags
	*/
	public function Whitespace_inside_BBCodes_is_ignored()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[B  /]',
			'<rt><B>[B  /]</B></rt>'
		);
	}

	/**
	* @test
	* @depends BBCode_tags_can_be_used_as_singletons_like_self_closing_XML_tags
	*/
	public function Junk_after_the_slash_of_a_self_closing_BBCode_tag_generates_a_warning()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[B /z]',
			'<pt>[B /z]</pt>',
			array(
				'warning' => array(
					array(
						'pos'    => 4,
						'msg'    => 'Unexpected character: expected $1%s found $2%s',
						'params' => array(']', 'z')
					)
				)
			)
		);
	}

	/**
	* @test
	* @depends Simple_BBcodes_are_parsed
	*/
	public function Junk_after_the_name_of_a_closing_bbcode_tag_generates_a_warning()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[B]xxx[/B ]',
			'<rt><B><st>[B]</st>xxx[/B ]</B></rt>',
			array(
				'warning' => array(
					array(
						'pos'    => 9,
						'msg'    => 'Unexpected character: expected $1%s found $2%s',
						'params' => array(']', ' ')
					)
				)
			)
		);
	}

	/**
	* @test
	*/
	public function A_BBCode_can_map_to_a_tag_of_a_different_name()
	{
		$this->cb->BBCodes->addBBCode('X', array('tagName' => 'Z'));

		$this->assertParsing(
			'[X/]',
			'<rt><Z>[X/]</Z></rt>'
		);
	}

	public function testAnEqualSignFollowingTheTagNameDefinesTheValueOfTheDefaultAttribute()
	{
		$this->cb->BBCodes->addBBCode('X', array(
			'defaultAttr' => 'z'
		));

		$this->cb->addTagAttribute('X', 'z', 'text', array('isRequired' => false));

		$this->assertParsing(
			'[X="123"][/X]',
			'<rt><X z="123"><st>[X="123"]</st><et>[/X]</et></X></rt>'
		);
	}

	/**
	* @test
	* @depends A_BBCode_can_map_to_a_tag_of_a_different_name
	* @depends testAnEqualSignFollowingTheTagNameDefinesTheValueOfTheDefaultAttribute
	*/
	public function If_no_default_attribute_is_specified_the_name_of_the_BBCode_is_used_as_the_name_of_the_default_attribute()
	{
		$this->cb->BBCodes->addBBCode('X', array('tagName' => 'Z'));

		$this->cb->addTagAttribute('Z', 'x', 'text', array('isRequired' => false));

		$this->assertParsing(
			'[X="123"][/X]',
			'<rt><Z x="123"><st>[X="123"]</st><et>[/X]</et></Z></rt>'
		);
	}
}