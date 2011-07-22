<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\Parser;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\TextFormatter\Plugins\BBCodesParser
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

	/**
	* @test
	*/
	public function Overlapping_tags_are_sorted_out()
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
						'msg'    => 'Unexpected character: expected %1$s found %2$s',
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
						'msg'    => 'Unexpected character: expected %1$s found %2$s',
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

	/**
	* @test
	*/
	public function An_equal_sign_following_the_tag_name_defines_the_value_of_the_default_attribute()
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
	* @depends An_equal_sign_following_the_tag_name_defines_the_value_of_the_default_attribute
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

	/**
	* @test
	*/
	public function An_attribute_at_the_end_of_a_tag_with_no_specified_value_is_considered_an_empty_string()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			'[X x=][/X]',
			'<rt><X x=""><st>[X x=]</st><et>[/X]</et></X></rt>'
		);
	}

	/**
	* @test
	*/
	public function An_attribute_in_the_middle_of_a_tag_with_no_specified_value_is_considered_an_empty_string()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			'[X x= y=2][/X]',
			'<rt><X x=""><st>[X x= y=2]</st><et>[/X]</et></X></rt>'
		);
	}

	/**
	* @test
	*/
	public function A_default_attribute_with_no_specified_value_is_considered_an_empty_string()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			'[X= ][/X]',
			'<rt><X x=""><st>[X= ]</st><et>[/X]</et></X></rt>'
		);
	}

	/**
	* @test
	*/
	public function An_attribute_not_followed_by_an_equal_sign_is_ignored()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text', array('isRequired' => false));

		$this->assertParsing(
			'[X x][/X]',
			'<rt><X><st>[X x]</st><et>[/X]</et></X></rt>'
		);
	}

	/**
	* @test
	*/
	public function An_unterminated_self_closing_BBCode_at_the_end_of_the_text_is_ignored()
	{
		$this->cb->BBCodes->addBBCode('X');

		$this->assertParsing(
			'[X /',
			'<pt>[X /</pt>'
		);
	}


	/**
	* @test
	*/
	public function An_unterminated_start_tag_with_a_default_attribute_at_the_end_of_the_text_is_ignored()
	{
		$this->cb->BBCodes->addBBCode('X');

		$this->assertParsing(
			'[X=',
			'<pt>[X=</pt>'
		);
	}

	/**
	* @test
	*/
	public function An_unterminated_BBCode_with_an_attribute_name_that_extends_till_the_end_of_the_text_is_ignored()
	{
		$this->cb->BBCodes->addBBCode('X');

		$this->assertParsing(
			'[X attr',
			'<pt>[X attr</pt>',
			array(
				'debug' => array(
					array(
						'pos'    => 3,
						'msg'    => 'Attribute name seems to extend till the end of text'
					)
				)
			)
		);
	}

	/**
	* @test
	*/
	public function Junk_characters_at_the_start_of_an_attribute_name_are_detected()
	{
		$this->cb->BBCodes->addBBCode('X');

		$this->assertParsing(
			'[X !b=1 /]',
			'<pt>[X !b=1 /]</pt>',
			array(
				'warning' => array(
					array(
						'pos'    => 3,
						'msg'    => 'Unexpected character %s',
						'params' => array('!')
					)
				)
			)
		);
	}

	/**
	* @test
	*/
	public function Junk_characters_in_an_attribute_name_are_detected()
	{
		$this->cb->BBCodes->addBBCode('X');

		$this->assertParsing(
			'[X a!b=1 /]',
			'<pt>[X a!b=1 /]</pt>',
			array(
				'warning' => array(
					array(
						'pos'    => 4,
						'msg'    => 'Unexpected character %s',
						'params' => array('!')
					)
				)
			)
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_can_be_enclosed_within_single_quotes()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			"[X x='abc' /]",
			'<rt><X x="abc">[X x=\'abc\' /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_can_be_enclosed_within_double_quotes()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			'[X x="abc" /]',
			'<rt><X x="abc">[X x="abc" /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_within_single_quotes_can_contain_spaces()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			"[X x=' a b c ' /]",
			'<rt><X x=" a b c ">[X x=\' a b c \' /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_within_double_quotes_can_contain_spaces()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			'[X x=" a b c " /]',
			'<rt><X x=" a b c ">[X x=" a b c " /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_within_single_quotes_can_contain_single_quotes_each_escaped_with_a_backslash()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			"[X x='\\'a\\'b\\'c\\'' /]",
			"<rt><X x=\"'a'b'c'\">[X x='\\'a\\'b\\'c\\'' /]</X></rt>"
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_within_double_quotes_can_contain_double_quotes_each_escaped_with_a_backslash()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			'[X x="\\"a\\"b\\"c\\"" /]',
			'<rt><X x="&quot;a&quot;b&quot;c&quot;">[X x="\\"a\\"b\\"c\\"" /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_within_single_quotes_can_contain_unescaped_double_quotes()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			'[X x=\'"a"b"c"\' /]',
			'<rt><X x="&quot;a&quot;b&quot;c&quot;">[X x=\'"a"b"c"\' /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_within_double_quotes_can_contain_unescaped_single_quotes()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			"[X x=\"'a'b'c'\" /]",
			"<rt><X x=\"'a'b'c'\">[X x=\"'a'b'c'\" /]</X></rt>"
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_within_single_quotes_can_contain_backslashes_each_escaped_with_another_backslash()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			"[X x='\\\\a\\\\b\\\\c\\\\' /]",
			'<rt><X x="\\a\\b\\c\\">[X x=\'\\\\a\\\\b\\\\c\\\\\' /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_within_double_quotes_can_contain_backslashes_each_escaped_with_another_backslash()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			'[X x="\\\\a\\\\b\\\\c\\\\" /]',
			'<rt><X x="\\a\\b\\c\\">[X x="\\\\a\\\\b\\\\c\\\\" /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function A_backslash_before_a_regular_character_is_preserved()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			'[X x="a\\b\\c" /]',
			'<rt><X x="a\\b\\c">[X x="a\\b\\c" /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_within_single_quotes_can_contain_newlines()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			"[X x='\na\nb\nc\n' /]",
			"<rt><X x=\"&#10;a&#10;b&#10;c&#10;\">[X x='\na\nb\nc\n' /]</X></rt>"
		);
	}

	/**
	* @test
	*/
	public function Attribute_values_within_double_quotes_can_contain_newlines()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			"[X x=\"\na\nb\nc\n\" /]",
			"<rt><X x=\"&#10;a&#10;b&#10;c&#10;\">[X x=\"\na\nb\nc\n\" /]</X></rt>"
		);
	}

	/**
	* @test
	* @depends An_attribute_not_followed_by_an_equal_sign_is_ignored
	*/
	public function An_attribute_value_not_within_quotes_cannot_contain_spaces()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			"[X x=a b /]",
			'<rt><X x="a">[X x=a b /]</X></rt>'
		);
	}

	/**
	* @test
	*/
	public function An_attribute_value_not_within_quotes_cannot_contain_newlines()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			"[X x=a\nb /]",
			"<pt>[X x=a\nb /]</pt>"
		);
	}

	/**
	* @test
	*/
	public function An_unterminated_attribute_that_start_with_a_quote_generates_a_warning()
	{
		$this->cb->BBCodes->addBBCode('X');
		$this->cb->addTagAttribute('X', 'x', 'text');

		$this->assertParsing(
			"[X x='a /]",
			"<pt>[X x='a /]</pt>",
			array(
				'warning' => array(
					array(
						'pos' => 5,
						'msg' => 'Could not find matching quote'
					)
				)
			)
		);
	}

	/**
	* @test
	*/
	public function Malformed_BBCodes_are_ignored()
	{
		$this->cb->BBCodes->addBBCode('X');

		$this->assertParsing(
			'[X ',
			'<pt>[X </pt>'
		);
	}

	/**
	* @test
	*/
	public function A_BBCode_with_autoClose_enabled_is_automatically_closed_after_the_start_tag()
	{
		$this->cb->BBCodes->addBBCode('X', array('autoClose' => true));

		$this->assertParsing(
			'xxx[X]xxx',
			'<rt>xxx<X>[X]</X>xxx</rt>'
		);
	}

	/**
	* @test
	*/
	public function A_BBCode_with_autoClose_enabled_is_not_automatically_closed_after_the_start_tag_if_it_is_immediately_followed_by_its_end_tag()
	{
		$this->cb->BBCodes->addBBCode('X', array('autoClose' => true));

		$this->assertParsing(
			'xxx[X][/X]xxx',
			'<rt>xxx<X><st>[X]</st><et>[/X]</et></X>xxx</rt>'
		);
	}

	/**
	* @test
	*/
	public function Attributes_listed_in_contentAttrs_use_everything_from_the_end_of_the_start_tag_and_what_could_be_the_end_tag_as_value_if_a_value_was_not_given()
	{
		$this->cb->BBCodes->addBBCode('X', array('contentAttrs' => array('b')));
		$this->cb->addTagAttribute('X', 'b', 'text');

		$this->assertParsing(
			'[X]xxx[/X]',
			'<rt><X b="xxx"><st>[X]</st>xxx<et>[/X]</et></X></rt>'
		);
	}

	/**
	* @test
	*/
	public function Attributes_listed_in_contentAttrs_are_not_overwritten_if_a_value_is_given()
	{
		$this->cb->BBCodes->addBBCode('X', array('contentAttrs' => array('b')));
		$this->cb->addTagAttribute('X', 'b', 'text');

		$this->assertParsing(
			'[X b="yyy"]xxx[/X]',
			'<rt><X b="yyy"><st>[X b="yyy"]</st>xxx<et>[/X]</et></X></rt>'
		);
	}

	/**
	* @test
	*/
	public function When_looking_for_the_end_tag_while_capturing_content_for_contentAttrs_the_search_is_case_insensitive()
	{
		$this->cb->BBCodes->addBBCode('X', array('contentAttrs' => array('b')));
		$this->cb->addTagAttribute('X', 'b', 'text');

		$this->assertParsing(
			'[X]xxx[/x]',
			'<rt><X b="xxx"><st>[X]</st>xxx<et>[/x]</et></X></rt>'
		);
	}

	/**
	* @test
	* @depends BBCode_tags_can_use_a_colon_followed_by_digits_as_a_suffix_to_control_how_start_tags_and_end_tags_are_paired
	*/
	public function When_looking_for_the_end_tag_while_capturing_content_for_contentAttrs_the_suffix_is_taken_into_account()
	{
		$this->cb->BBCodes->addBBCode('X', array('contentAttrs' => array('b')));
		$this->cb->addTagAttribute('X', 'b', 'text');

		$this->assertParsing(
			'[X:1]xxx[/X][/X:1]',
			'<rt><X b="xxx[/X]"><st>[X:1]</st>xxx[/X]<et>[/X:1]</et></X></rt>'
		);
	}

	/**
	* @testdox Multiple attributes can use the BBCode's content
	*/
	public function testMultipleContentAttrs()
	{
		$this->cb->BBCodes->addBBCode('X', array('contentAttrs' => array('a', 'b')));
		$this->cb->addTagAttribute('X', 'a', 'text');
		$this->cb->addTagAttribute('X', 'b', 'text');

		$this->assertParsing(
			'[X]xxx[/X]',
			'<rt><X a="xxx" b="xxx"><st>[X]</st>xxx<et>[/X]</et></X></rt>'
		);
	}
}