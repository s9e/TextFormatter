<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Tests\Test,
	s9e\TextFormatter\ConfigBuilder;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\TextFormatter\Plugins\BBCodesConfig
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
	public function addBBCodeAlias_throws_an_exception_if_the_BBCode_already_exists()
	{
		$this->cb->BBCodes->addBBCode('A');
		$this->cb->BBCodes->addBBCodeAlias('A', 'A');
	}

	/**
	* @test
	* @depend BBCodes_are_mapped_to_a_tag_of_the_same_name_by_default
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name '*'
	*/
	public function addBBCodeAlias_cannot_create_an_alias_to_an_invalid_tag_name()
	{
		$this->cb->BBCodes->addBBCodeAlias('A', '*');
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

	/**
	* @test
	* @depends BBCodes_are_mapped_to_a_tag_of_the_same_name_by_default
	*/
	public function Can_return_all_options_of_a_BBCode()
	{
		$this->cb->BBCodes->addBBCode('A');

		$this->assertArrayMatches(
			array('tagName' => 'A'),
			$this->cb->BBCodes->getBBCodeOptions('A')
		);
	}

	/**
	* @test
	* @depends BBCodes_are_mapped_to_a_tag_of_the_same_name_by_default
	*/
	public function Can_return_the_value_of_an_option_of_a_BBCode()
	{
		$this->cb->BBCodes->addBBCode('A');
		$this->assertSame('A', $this->cb->BBCodes->getBBCodeOption('A', 'tagName'));
	}

	/**
	* @test
	* @depends Can_return_the_value_of_an_option_of_a_BBCode
	*/
	public function Can_return_the_value_of_an_option_of_a_BBCode_even_if_it_is_null()
	{
		$this->cb->BBCodes->addBBCode('A', array('autoClose' => null));
		$this->assertNull($this->cb->BBCodes->getBBCodeOption('A', 'autoClose'));
	}

	/**
	* @test
	* @depends Can_return_all_options_of_a_BBCode
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage BBCode 'A' does not exist
	*/
	public function getBBCodeOptions_throws_an_exception_if_the_BBCode_does_not_exist()
	{
		$this->cb->BBCodes->getBBCodeOptions('A');
	}

	/**
	* @test
	* @depends Can_return_the_value_of_an_option_of_a_BBCode
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage BBCode 'A' does not exist
	*/
	public function getBBCodeOption_throws_an_exception_if_the_BBCode_does_not_exist()
	{
		$this->cb->BBCodes->getBBCodeOption('A', 'tagName');
	}

	/**
	* @test
	* @depends Can_return_the_value_of_an_option_of_a_BBCode
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Unknown option 'XYZ' from BBCode 'A'
	*/
	public function getBBCodeOption_throws_an_exception_if_the_option_does_not_exist()
	{
		$this->cb->BBCodes->addBBCode('A');
		$this->cb->BBCodes->getBBCodeOption('A', 'XYZ');
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid attribute name '**'
	*/
	public function setBBCodeOption_cannot_set_a_defaultAttr_with_an_invalid_name()
	{
		$this->cb->BBCodes->addBBCode('A');
		$this->cb->BBCodes->setBBCodeOption('A', 'defaultAttr', '**');
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid attribute name '**'
	*/
	public function setBBCodeOption_cannot_set_a_contentAttr_with_an_invalid_name()
	{
		$this->cb->BBCodes->addBBCode('A');
		$this->cb->BBCodes->setBBCodeOption('A', 'contentAttr', '**');
	}

	/**
	* @test
	* @depends Can_tell_whether_a_BBCode_exists
	*/
	public function addBBCodeFromExample_works_on_simple_BBCodes()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[B]{TEXT}[/B]', '<b>{TEXT}</b>');
		$this->assertTrue($this->cb->BBCodes->BBCodeExists('B'));
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Cannot interpret the BBCode definition
	*/
	public function addBBCodeFromExample_throws_an_exception_if_the_definition_is_malformed()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[foo==]{TEXT}[/foo]', '');
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid XML in template - error was: Premature end of data
	*/
	public function addBBCodeFromExample_throws_an_exception_if_the_template_is_not_wellformed_XML()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[HR][/HR]', '<hr>');
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Undefined placeholder {ID} found in template
	*/
	public function addBBCodeFromExample_throws_an_exception_if_an_undefined_placeholder_is_found_in_an_attribute()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[B][/B]', '<b id="{ID}"></b>');
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Undefined placeholder {TEXT} found in template
	*/
	public function addBBCodeFromExample_throws_an_exception_if_an_undefined_placeholder_is_found_anywhere()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[B][/B]', '<b>{TEXT}{</b>');
	}

	/**
	* @test
	* @expectedException RuntimeException
	* @expectedExceptionMessage ALLOW_UNSAFE_TEMPLATES
	*/
	public function addBBCodeFromExample_throws_an_exception_if_a_TEXT_placeholder_is_found_in_an_attribute()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[B={TEXT}][/B]', '<b id="{TEXT}"></b>');
	}

	/**
	* @test
	*/
	public function addBBCodeFromExample_does_not_throw_an_exception_if_a_TEXT_placeholder_is_found_in_an_attribute_but_ALLOW_UNSAFE_TEMPLATES_flag_is_set()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={TEXT}][/B]',
			'<b id="{TEXT}"></b>',
			ConfigBuilder::ALLOW_UNSAFE_TEMPLATES
		);
	}

	/**
	* @test
	* @depends addBBCodeFromExample_works_on_simple_BBCodes
	* @depends Can_return_the_value_of_an_option_of_a_BBCode
	*/
	public function addBBCodeFromExample_allows_a_single_start_tag_with_no_end_tag_and_enables_autoClose()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[HR]', '<hr />');
		$this->assertTrue($this->cb->BBCodes->BBCodeExists('HR'));
		$this->assertTrue($this->cb->BBCodes->getBBCodeOption('HR', 'autoClose'));
	}

	/**
	* @test
	* @depends addBBCodeFromExample_works_on_simple_BBCodes
	* @depends Can_return_the_value_of_an_option_of_a_BBCode
	*/
	public function addBBCodeFromExample_allows_a_self_closed_tag_and_enables_autoClose()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[HR /]', '<hr />');
		$this->assertTrue($this->cb->BBCodes->BBCodeExists('HR'));
		$this->assertTrue($this->cb->BBCodes->getBBCodeOption('HR', 'autoClose'));
	}

	/**
	* @test
	* @depends addBBCodeFromExample_works_on_simple_BBCodes
	* @depends Can_return_the_value_of_an_option_of_a_BBCode
	*/
	public function addBBCodeFromExample_handles_default_attribute_and_gives_it_the_same_name_as_the_tag()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[A={URL}]{TEXT}[/A]',
			'<a href="{URL}">{TEXT}</a>'
		);

		$this->assertTrue($this->cb->BBCodes->BBCodeExists('A'));
		$this->assertSame('a', $this->cb->BBCodes->getBBCodeOption('A', 'defaultAttr'));

		$this->assertTrue($this->cb->attributeExists('A', 'a'));
		$this->assertSame('url', $this->cb->getTagAttributeOption('A', 'a', 'type'));
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_default_attribute_and_gives_it_the_same_name_as_the_tag
	*/
	public function addBBCodeFromExample_handles_single_preFilter_callback_in_attribute()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={ID;preFilter=strtolower}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertArrayMatches(
			array(
				array('callback' => 'strtolower')
			),
			$this->cb->getTagAttributeOption('B', 'b', 'preFilter')
		);
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_single_preFilter_callback_in_attribute
	*/
	public function addBBCodeFromExample_handles_multiple_preFilter_callbacks_in_attribute()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={ID;preFilter=strtolower,ucfirst}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertArrayMatches(
			array(
				array('callback' => 'strtolower'),
				array('callback' => 'ucfirst')
			),
			$this->cb->getTagAttributeOption('B', 'b', 'preFilter')
		);
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_single_preFilter_callback_in_attribute
	* @expectedException RuntimeException
	* @expectedExceptionMessage Callback 'system' is not allowed
	*/
	public function addBBCodeFromExample_rejects_unauthorized_preFilter_callbacks_in_attribute()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={ID;preFilter=system}]{TEXT}[/B]',
			'<b/>'
		);
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_default_attribute_and_gives_it_the_same_name_as_the_tag
	*/
	public function addBBCodeFromExample_handles_single_postFilter_callback_in_attribute()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={ID;postFilter=strtolower}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertArrayMatches(
			array(
				array('callback' => 'strtolower')
			),
			$this->cb->getTagAttributeOption('B', 'b', 'postFilter')
		);
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_single_postFilter_callback_in_attribute
	*/
	public function addBBCodeFromExample_handles_multiple_postFilter_callbacks_in_attribute()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={ID;postFilter=strtolower,ucfirst}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertArrayMatches(
			array(
				array('callback' => 'strtolower'),
				array('callback' => 'ucfirst')
			),
			$this->cb->getTagAttributeOption('B', 'b', 'postFilter')
		);
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_single_postFilter_callback_in_attribute
	* @expectedException RuntimeException
	* @expectedExceptionMessage Callback 'system' is not allowed
	*/
	public function addBBCodeFromExample_rejects_unauthorized_postFilter_callbacks_in_attribute()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={ID;postFilter=system}]{TEXT}[/B]',
			'<b/>'
		);
	}

	/**
	* @test
	* @depends addBBCodeFromExample_rejects_unauthorized_preFilter_callbacks_in_attribute
	*/
	public function Custom_callbacks_can_be_added_via_BBCodesConfig_allowPhaseFiltersCallback()
	{
		$this->cb->BBCodes->allowPhaseFiltersCallback('system');

		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={ID;preFilter=system}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertArrayMatches(
			array(
				array('callback' => 'system')
			),
			$this->cb->getTagAttributeOption('B', 'b', 'preFilter')
		);
	}

	static public function foo($str)
	{
		return $str;
	}

	/**
	* @test
	* @depends addBBCodeFromExample_rejects_unauthorized_preFilter_callbacks_in_attribute
	*/
	public function Static_method_callbacks_can_be_added_via_BBCodesConfig_allowPhaseFiltersCallback()
	{
		$this->cb->BBCodes->allowPhaseFiltersCallback(array(__CLASS__, 'foo'));

		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={ID;preFilter=' . __CLASS__ . '::foo}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertArrayMatches(
			array(
				array('callback' => array(__CLASS__, 'foo'))
			),
			$this->cb->getTagAttributeOption('B', 'b', 'preFilter')
		);
	}

	/**
	* @test
	* @depends addBBCodeFromExample_works_on_simple_BBCodes
	*/
	public function addBBCodeFromExample_does_not_create_an_attribute_for_the_tag_content_if_it_is_TEXT_with_no_other_options_set()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[A={URL}]{TEXT}[/A]',
			'<a href="{URL}">{TEXT}</a>'
		);

		$this->assertTrue($this->cb->BBCodes->BBCodeExists('A'));
		$this->assertArrayNotHasKey('contentAttr', $this->cb->BBCodes->getBBCodeOptions('A'));
	}

	/**
	* @test
	* @depends addBBCodeFromExample_works_on_simple_BBCodes
	* @depends Can_return_the_value_of_an_option_of_a_BBCode
	*/
	public function addBBCodeFromExample_creates_an_attribute_named_content_for_the_tag_content()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[EMAIL]{EMAIL}[/EMAIL]',
			'<b>{EMAIL}</b>'
		);

		$this->assertTrue($this->cb->BBCodes->BBCodeExists('EMAIL'));
		$this->assertSame('content', $this->cb->BBCodes->getBBCodeOption('EMAIL', 'contentAttr'));
	}

	/**
	* @test
	* @depends addBBCodeFromExample_creates_an_attribute_named_content_for_the_tag_content
	*/
	public function addBBCodeFromExample_replaces_placeholders_in_attributes()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[EMAIL]{EMAIL}[/EMAIL]',
			'<a href="mailto:{EMAIL}">{EMAIL}</a>'
		);

		$this->assertSame(
			'<xsl:template match="EMAIL"><a href="mailto:{@content}"><xsl:value-of select="@content"/></a></xsl:template>',
			$this->cb->getTagXSL('EMAIL')
		);
	}

	/**
	* @test
	* @depends addBBCodeFromExample_creates_an_attribute_named_content_for_the_tag_content
	*/
	public function addBBCodeFromExample_replaces_placeholders_in_content()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[EMAIL]{EMAIL}[/EMAIL]',
			'Mail me at {EMAIL}'
		);

		$this->assertSame(
			'<xsl:template match="EMAIL">Mail me at <xsl:value-of select="@content"/></xsl:template>',
			$this->cb->getTagXSL('EMAIL')
		);
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Attribute 'foo' is defined twice
	*/
	public function addBBCodeFromExample_throws_an_exception_on_duplicate_attributes()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[foo={URL1} FOO={URL2}]{TEXT}[/foo]', '<b/>');
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Placeholder {URL} is used twice
	*/
	public function addBBCodeFromExample_throws_an_exception_on_duplicate_placeholders()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[foo={URL} bar={URL}]{TEXT}[/foo]', '<b/>');
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Undefined placeholder {COLOR}
	*/
	public function addBBCodeFromExample_throws_an_exception_on_undefined_placeholders_used_in_attributes()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[foo={URL}]{TEXT}[/foo]',
			'<b style="color:{COLOR}">{TEXT}</b>'
		);
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Undefined placeholder {TEXT2}
	*/
	public function addBBCodeFromExample_throws_an_exception_on_undefined_placeholders_used_in_content()
	{
		$this->cb->BBCodes->addBBCodeFromExample('[B]{TEXT}[/B]', '<b>{TEXT2}</b>');
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_default_attribute_and_gives_it_the_same_name_as_the_tag
	*/
	public function addBBCodeFromExample_allows_arbitrary_options_in_attribute()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={ID;foo=bar;baz=quux}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertSame('bar', $this->cb->getTagAttributeOption('B', 'b', 'foo'));
		$this->assertSame('quux', $this->cb->getTagAttributeOption('B', 'b', 'baz'));
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_default_attribute_and_gives_it_the_same_name_as_the_tag
	*/
	public function addBBCodeFromExample_handles_REGEXP_placeholders()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={REGEXP=/^foo$/}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertSame('regexp', $this->cb->getTagAttributeOption('B', 'b', 'type'));
		$this->assertSame('/^foo$/', $this->cb->getTagAttributeOption('B', 'b', 'regexp'));
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_default_attribute_and_gives_it_the_same_name_as_the_tag
	*/
	public function addBBCodeFromExample_handles_COMPOUND_placeholders()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={COMPOUND=/^(?P<foo>foo)$/}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertSame('compound', $this->cb->getTagAttributeOption('B', 'b', 'type'));
		$this->assertSame('/^(?P<foo>foo)$/', $this->cb->getTagAttributeOption('B', 'b', 'regexp'));
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_default_attribute_and_gives_it_the_same_name_as_the_tag
	*/
	public function addBBCodeFromExample_handles_RANGE_placeholders()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={RANGE=-10,20}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertSame('range', $this->cb->getTagAttributeOption('B', 'b', 'type'));
		$this->assertSame(-10, $this->cb->getTagAttributeOption('B', 'b', 'min'));
		$this->assertSame(20, $this->cb->getTagAttributeOption('B', 'b', 'max'));
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_default_attribute_and_gives_it_the_same_name_as_the_tag
	*/
	public function addBBCodeFromExample_handles_CHOICE_placeholders_and_turns_them_into_regexps()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={CHOICE=foo,bar,quux}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertSame('regexp', $this->cb->getTagAttributeOption('B', 'b', 'type'));
		$this->assertSame('#^(?=[bfq])(?:bar|foo|quux)$#iD', $this->cb->getTagAttributeOption('B', 'b', 'regexp'));
	}

	/**
	* @test
	* @depends addBBCodeFromExample_handles_CHOICE_placeholders_and_turns_them_into_regexps
	*/
	public function addBBCodeFromExample_handles_CHOICE_placeholders_with_Unicode_values()
	{
		$this->cb->BBCodes->addBBCodeFromExample(
			'[B={CHOICE=オレンジ,桜}]{TEXT}[/B]',
			'<b/>'
		);

		$this->assertSame('regexp', $this->cb->getTagAttributeOption('B', 'b', 'type'));
		$this->assertSame('#^(?=[オ桜])(?:オレンジ|桜)$#iDu', $this->cb->getTagAttributeOption('B', 'b', 'regexp'));
	}

	/**
	* @test
	*/
	public function getJSParser_returns_the_source_of_its_Javascript_parser()
	{
		$this->assertStringEqualsFile(
			__DIR__ . '/../../src/Plugins/BBCodesParser.js',
			$this->cb->BBCodes->getJSParser()
		);
	}

	/**
	* @test
	*/
	public function BBCode_names_are_preserved_in_Javascript_config()
	{
		include_once __DIR__ . '/../../src/JSParserGenerator.php';
		$this->cb->BBCodes->addBBCode('FOOBAR');

		$this->assertStringStartsWith(
			'{bbcodesConfig:{"FOOBAR"',
			$this->call(
				's9e\\TextFormatter\\JSParserGenerator',
				'encodeConfig',
				array(
					$this->cb->BBCodes->getJSConfig(),
					$this->cb->BBCodes->getJSConfigMeta()
				)
			)
		);
	}
}