<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\ConfigBuilder;

include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\ConfigBuilder
*/
class ConfigBuilderTest extends Test
{
	public function testCanLoadPlugins()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Plugins\\EmoticonsConfig',
			$this->cb->loadPlugin('Emoticons')
		);
	}

	/**
	* @depends testCanLoadPlugins
	*/
	public function testLoadedPluginsAreAvailableAsAPublicProperty()
	{
		$this->cb->loadPlugin('Emoticons');

		$this->assertObjectHasAttribute('Emoticons', $this->cb);
		$this->assertTrue(isset($this->cb->Emoticons), 'Could not assert that $this->cb->Emoticons is set');
	}

	/**
	* @depends testLoadedPluginsAreAvailableAsAPublicProperty
	*/
	public function testCanUnloadPluginsByUnsettingThem()
	{
		$this->cb->loadPlugin('Emoticons');
		unset($this->cb->Emoticons);

		$this->assertObjectNotHasAttribute('Emoticons', $this->cb);
	}

	/**
	* @depends testCanLoadPlugins
	*/
	public function testLoadsPluginOnMagicGet()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Plugins\\EmoticonsConfig',
			$this->cb->Emoticons
		);
	}

	/**
	* @test
	*/
	public function Has_a_magic_property_predefinedTags_that_loads_and_creates_an_instance_of_PredefinedTags()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\PredefinedTags',
			$this->cb->predefinedTags
		);
	}

	/**
	* @expectedException RuntimeException
	* @expectedExceptionMessage Undefined property: s9e\Toolkit\TextFormatter\ConfigBuilder::$foo
	*/
	public function testThrowsAnExceptionOnUnsupportedMagicGet()
	{
		$this->cb->foo;
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid plugin name "../foo"
	*/
	public function loadPlugin_throws_an_exception_on_invalid_plugin_name()
	{
		$this->cb->loadPlugin('../foo');
	}

	/**
	* @test
	* @expectedException RuntimeException
	* @expectedExceptionMessage Class 's9e\Toolkit\TextFormatter\Plugins\FoobarConfig' not found
	*/
	public function loadPlugin_throws_an_exception_on_unknown_plugin()
	{
		$this->cb->loadPlugin('Foobar');
	}

	public function testCanCreateTag()
	{
		$this->cb->addTag('a');
		$this->assertArrayHasKey('A', $this->cb->getTagsConfig());
	}

	/**
	* @depends testCanCreateTag
	*/
	public function testCanCreateTagWithOption()
	{
		$this->cb->addTag('a', array('foo' => 'bar'));

		$config = $this->cb->getTagsConfig();
		$this->assertArrayHasKey('foo', $config['A']);
		$this->assertSame('bar', $config['A']['foo']);
	}

	/**
	* @depends testCanCreateTag
	*/
	public function testCanTellIfTagExists()
	{
		$this->cb->addTag('a');
		$this->assertTrue($this->cb->tagExists('a'));
	}

	/**
	* @depends testCanTellIfTagExists
	*/
	public function testCanRemoveTag()
	{
		$this->cb->addTag('a');
		$this->cb->removeTag('a');
		$this->assertFalse($this->cb->tagExists('a'));
	}

	/**
	* @depends testCanCreateTag
	*/
	public function testCanCreateAttribute()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'href', 'url');

		$tagsConfig = $this->cb->getTagsConfig();
		$this->assertArrayHasKey('attrs', $tagsConfig['A']);
		$this->assertArrayHasKey('href', $tagsConfig['A']['attrs']);
	}

	/**
	* @depends testCanCreateAttribute
	*/
	public function testCanTellIfAttributeExists()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'href', 'url');
		$this->assertTrue($this->cb->attributeExists('a', 'href'));
	}

	/**
	* @depends testCanTellIfAttributeExists
	*/
	public function testCanRemoveAttributeIfItExists()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'href', 'url');
		$this->cb->removeAttribute('a', 'href');
		$this->assertFalse($this->cb->attributeExists('a', 'href'));
	}

	/**
	* @test
	* @depends testCanTellIfAttributeExists
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Tag 'A' does not have an attribute named 'href'
	*/
	public function Throws_an_exception_if_removeAttribute_is_called_for_an_attribute_that_does_not_exist()
	{
		$this->cb->addTag('a');
		$this->cb->removeAttribute('a', 'href');
	}

	/**
	* @depends testCanCreateAttribute
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Tag 'FOO' does not exist
	*/
	public function testCannotCreateAttributeOnNonExistingTag()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('foo', 'href', 'url');
	}

	/**
	* @depends testCanCreateAttribute
	*/
	public function testCanGetTagAttributeOptions()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'href', 'url', array('isRequired' => true));

		$this->assertArrayMatches(
			array('isRequired' => true),
			$this->cb->getTagAttributeOptions('a', 'href')
		);
	}

	/**
	* @depends testCanCreateAttribute
	*/
	public function testCanGetTagAttributeOption()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'href', 'url', array('isRequired' => true));

		$this->assertTrue($this->cb->getTagAttributeOption('a', 'href', 'isRequired'));
	}

	/**
	* @depends testCanGetTagAttributeOption
	*/
	public function testCanSetTagAttributeOption()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'href', 'url', array('isRequired' => true));

		$this->cb->setTagAttributeOption('a', 'href', 'isRequired', false);

		$this->assertFalse($this->cb->getTagAttributeOption('a', 'href', 'isRequired'));
	}

	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'XYZ*'
	*/
	public function testInvalidTagNamesAreRejected()
	{
		$this->cb->addTag('XYZ*');
	}

	/**
	* @depends testCanCreateAttribute
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid attribute name 'ns:href'
	*/
	public function testInvalidAttributeNamesAreRejected()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'ns:href', 'url');
	}

	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Tag 'TAG' already exists
	*/
	public function testDuplicateTagNamesAreRejected()
	{
		$this->cb->addTag('TAG');
		$this->cb->addTag('TAG');
	}

	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Attribute 'href' already exists
	*/
	public function testDuplicateAttributeNamesAreRejected()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'href', 'url');
		$this->cb->addTagAttribute('a', 'href', 'text');
	}

	/**
	* @depends testCanCreateAttribute
	*/
	public function testDifferentTagsCanHaveAttributesOfTheSameName()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'href', 'url');

		$this->cb->addTag('link');
		$this->cb->addTagAttribute('link', 'href', 'url');
	}

	/**
	* @depends testCanCreateTagWithOption
	* @depends testCanCreateAttribute
	*/
	public function testCanCreateTagWithAttributes()
	{
		$this->cb->addTag('a', array(
			'attrs' => array(
				// the attribute names are uppercased to make sure they actually go through
				// addTagAttribute where they are normalized
				'HREF'  => array('type' => 'url'),
				'TITLE' => array('type' => 'text')
			)
		));

		$expected = array(
			'A' => array(
				'attrs' => array(
					'href'  => array('type' => 'url'),
					'title' => array('type' => 'text')
				)
			)
		);

		$this->assertArrayMatches($expected, $this->cb->getTagsConfig());
	}

	/**
	* @depends testCanCreateTag
	*/
	public function testCanCreateRule()
	{
		$this->cb->addTag('a');
		$this->cb->addTag('b');
		$this->cb->addTagRule('a', 'closeParent', 'b');

		$expected = array(
			'A' => array(
				'rules' => array(
					'closeParent' => array(
						'B' => 'B'
					)
				)
			)
		);

		$this->assertArrayMatches($expected, $this->cb->getTagsConfig());
	}

	/**
	* @depends testCanCreateTag
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Tag 'FOO' does not exist
	*/
	public function testCannotCreateRuleOnNonExistentTag()
	{
		$this->cb->addTag('BAR');
		$this->cb->addTagRule('FOO', 'deny', 'BAR');
	}

	/**
	* @depends testCanCreateRule
	* @expectedException UnexpectedValueException
	* @expectedExceptionMessage Unknown rule action 'shootFoot'
	*/
	public function testCannotCreateUnknownRule()
	{
		$this->cb->addTag('FOO');
		$this->cb->addTag('BAR');
		$this->cb->addTagRule('FOO', 'shootFoot', 'BAR');
	}

	/**
	* @test
	* @depends testCanCreateRule
	*/
	public function Can_create_multiple_requireParent_rules_on_different_targets()
	{
		$this->cb->addTag('FOO');
		$this->cb->addTag('BAR');
		$this->cb->addTag('BAZ');
		$this->cb->addTagRule('FOO', 'requireParent', 'BAR');
		$this->cb->addTagRule('FOO', 'requireParent', 'BAZ');
	}

	/**
	* @depends testCanCreateTag
	*/
	public function testCanCreateRuleThatTargetsANonExistentTag()
	{
		$this->cb->addTag('FOO');
		$this->cb->addTagRule('FOO', 'deny', 'BAR');
	}

	/**
	* @depends testCanCreateRule
	*/
	public function testCanRemoveRule()
	{
		$this->cb->addTag('a');
		$this->cb->addTag('b');
		$this->cb->addTagRule('a', 'allow', 'b');
		$this->cb->addTagRule('a', 'deny', 'b');
		$this->cb->addTagRule('a', 'allow', 'b');
		$this->cb->removeRule('a', 'allow', 'b');

		$tagsConfig = $this->cb->getTagsConfig();

		$expected = array(
			'A' => array(
				'rules' => array(
					'allow' => array(),

					'deny' => array(
						'B' => 'B'
					)
				)
			)
		);

		$this->assertArrayMatches($expected, $tagsConfig);
	}

	/**
	* @depends testCanCreateTagWithOption
	* @depends testCanCreateRule
	*/
	public function testCanCreateTagWithRules()
	{
		$this->cb->addTag('b');
		$this->cb->addTag('c');
		$this->cb->addTag('d');

		$this->cb->addTag('a', array(
			'rules' => array(
				'allow' => array('B'),
				'deny'  => array('C', 'D')
			)
		));

		$expected = array(
			'A' => array(
				'rules' => array(
					'allow' => array(
						'B' => 'B'
					),
					'deny' => array(
						'C' => 'C',
						'D' => 'D'
					)
				)
			)
		);

		$this->assertArrayMatches($expected, $this->cb->getTagsConfig());
	}

	/**
	* @depends testCanCreateTag
	*/
	public function testCanSetUnknownTagOption()
	{
		$this->cb->addTag('a');
		$this->cb->setTagOption('a', 'myOption', true);

		$expected = array(
			'A' => array(
				'myOption' => true
			)
		);

		$this->assertArrayMatches($expected, $this->cb->getTagsConfig());
	}

	/**
	* @depends testCanCreateTag
	*/
	public function testDefaultOptionsAreSetOnNewTags()
	{
		$this->cb->addTag('a');

		$this->assertArrayMatches($this->cb->defaultTagOptions, $this->cb->getTagOptions('a'));
	}

	/**
	* @depends testDefaultOptionsAreSetOnNewTags
	*/
	public function testTagOptionsPhpTypeIsPreserved()
	{
		$this->cb->addTag('a', array('nestingLimit' => '123'));

		$this->assertSame(123, $this->cb->getTagOption('a', 'nestingLimit'));
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Tag 'FOO' does not exist
	*/
	public function getTagOptions_fails_on_unknown_tags()
	{
		$this->cb->getTagOptions('foo');
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Tag 'FOO' does not exist
	*/
	public function getTagOption_fails_on_unknown_tags()
	{
		$this->cb->getTagOption('foo', 'nestingLimit');
	}

	/**
	* @test
	* @depends testCanCreateTag
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Unknown option 'bar' from tag 'A'
	*/
	public function getTagOption_fails_on_unset_options()
	{
		$this->cb->addTag('a');
		$this->cb->getTagOption('a', 'bar');
	}

	/**
	* @test
	* @depends testCanCreateTag
	*/
	public function getTagOption_can_return_a_null_option()
	{
		$this->cb->addTag('a', array('bar' => null));
		$this->assertNull($this->cb->getTagOption('a', 'bar'));
	}

	/**
	* @depends testCanCreateTag
	*/
	public function testCanSetTagTemplate()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate('a', '<a/>');

		$this->assertSame(
			'<xsl:template match="A"><a/></xsl:template>',
			$this->cb->getTagXSL('a')
		);
	}

	/**
	* @depends testCanCreateTag
	*/
	public function testCanSetTagXsl()
	{
		$this->cb->addTag('a');
		$this->cb->setTagXSL('a', '<xsl:template match="A"><a/></xsl:template>');

		$this->assertSame(
			'<xsl:template match="A"><a/></xsl:template>',
			$this->cb->getTagXSL('a')
		);
	}

	/**
	* @expectedException InvalidArgumentException FOO
	*/
	public function testCannotSetTagTemplateOnUnknownTag()
	{
		$this->cb->setTagTemplate('foo', '<a/>');
	}

	/**
	* @depends testCanCreateTag
	* @expectedException InvalidArgumentException Invalid
	*/
	public function testCannotSetTagTemplateWithInvalidXsl()
	{
		$this->cb->addTag('br');
		$this->cb->setTagTemplate('br', '<br>');
	}

	/**
	* @expectedException InvalidArgumentException FOO
	*/
	public function testCannotGetTagXslOnUnknownTag()
	{
		$this->cb->getTagXSL('FOO');
	}

	/**
	* @depends testCanCreateTag
	* @expectedException InvalidArgumentException No
	*/
	public function testCannotGetTagXslOnTagWithoutXsl()
	{
		$this->cb->addTag('FOO');
		$this->cb->getTagXSL('FOO');
	}

	/**
	* @depends testCanCreateTagWithOption
	* @depends testCanSetTagTemplate
	*/
	public function testCanCreateTagWithTemplate()
	{
		$this->cb->addTag('a', array(
			'template' => '<a/>'
		));

		$this->assertSame(
			'<xsl:template match="A"><a/></xsl:template>',
			$this->cb->getTagXSL('a')
		);
	}

	/**
	* @depends testCanCreateTagWithOption
	* @depends testCanSetTagXsl
	*/
	public function testCanCreateTagWithXsl()
	{
		$this->cb->addTag('a', array(
			'xsl' => '<xsl:template match="A"><a/></xsl:template>'
		));

		$this->assertSame(
			'<xsl:template match="A"><a/></xsl:template>',
			$this->cb->getTagXSL('a')
		);
	}

	/**
	* @depends testCanSetTagXsl
	*/
	public function testCanSetTagXslWithPreservedWhitespace()
	{
		$xsl =
			'<xsl:template match="A">
				<div>
					<a/>
				</div>
			</xsl:template>';

		$this->cb->addTag('a');
		$this->cb->setTagXSL('a', $xsl, ConfigBuilder::PRESERVE_WHITESPACE);

		$this->assertSame(
			$xsl,
			$this->cb->getTagXSL('a')
		);
	}

	/**
	* @depends testCanSetTagTemplate
	* @expectedException RuntimeException ALLOW_INSECURE_TEMPLATES
	*/
	public function testCannotSetTagTemplateWithVariableInScriptSrc()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<script src="http://{TEXT}"/>'
		);
	}

	/**
	* @depends testCannotSetTagTemplateWithVariableInScriptSrc
	*/
	public function testCanSetTagTemplateWithVariableInScriptSrcWithInsecureFlag()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<script src="http://{TEXT}"/>',
			ConfigBuilder::ALLOW_INSECURE_TEMPLATES
		);
	}

	/**
	* @depends testCanSetTagTemplate
	* @expectedException RuntimeException ALLOW_INSECURE_TEMPLATES
	*/
	public function testCannotSetTagTemplateWithVariableInScriptContent()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<script><xsl:value-of select="@LOL"/></script>'
		);
	}

	/**
	* @depends testCannotSetTagTemplateWithVariableInScriptContent
	*/
	public function testCanSetTagTemplateWithVariableInScriptContentWithInsecureFlag()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<script><xsl:value-of select="@LOL"/></script>',
			ConfigBuilder::ALLOW_INSECURE_TEMPLATES
		);
	}

	/**
	* @depends testCanSetTagTemplate
	* @expectedException RuntimeException ALLOW_INSECURE_TEMPLATES
	*/
	public function testCannotSetTagTemplateWithDisableOutputEscaping()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<xsl:value-of select="@LOL" disable-output-escaping="yes" />'
		);
	}

	/**
	* @depends testCannotSetTagTemplateWithDisableOutputEscaping
	*/
	public function testCanSetTagTemplateWithDisableOutputEscapingWithInsecureFlag()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<xsl:value-of select="@LOL" disable-output-escaping="yes" />',
			ConfigBuilder::ALLOW_INSECURE_TEMPLATES
		);
	}

	public function testCanSetCustomFilter()
	{
		$this->cb->setFilter('foo', array('callback' => 'trim'));

		$this->assertArrayMatches(
			array(
				'foo' => array(
					'callback' => 'trim'
				)
			),
			$this->cb->getFiltersConfig()
		);
	}

	/**
	* @depends testCanSetCustomFilter
	*/
	public function testCustomFiltersArePassedTheAttributeValueIfNoParamsArrayWasSpecified()
	{
		$this->cb->setFilter('foo', array('callback' => 'trim'));

		$filtersConfig = $this->cb->getFiltersConfig();

		$this->assertEquals(
			array(
				'callback' => 'trim',
				'params'   => array('attrVal' => null)
			),
			$filtersConfig['foo']			
		);
	}

	/**
	* @depends testCanSetCustomFilter
	*/
	public function testCanSetCustomFilterWithExtraConfig()
	{
		$filterConf = array(
			'callback' => function($value, $min, $max) {},
			'params'   => array('attrVal'  => false, 2, 5)
		);

		$this->cb->setFilter('range', $filterConf);

		$this->assertArrayMatches(
			array('range' => $filterConf),
			$this->cb->getFiltersConfig()
		);
	}

	/**
	* @test
	* @expectedException InvalidArgumentException callback
	*/
	public function setFilter_throws_an_exception_on_invalid_callback()
	{
		$this->cb->setFilter('foo', array('callback' => 'bar'));
	}

	/**
	* @test
	*/
	public function HTTP_and_HTTPS_schemes_are_allowed_by_default()
	{
		$this->assertEquals(
			array('http', 'https'),
			$this->cb->getAllowedSchemes()
		);
	}

	public function testUrlFilterAllowsDefaultSchemes()
	{
		$filtersConfig = $this->cb->getFiltersConfig();

		$this->assertArrayHasNestedKeys(
			$filtersConfig,
			'url',
			'allowedSchemes'
		);

		$this->assertRegexp($filtersConfig['url']['allowedSchemes'], 'http');
		$this->assertRegexp($filtersConfig['url']['allowedSchemes'], 'https');
	}

	/**
	* @depends testUrlFilterAllowsDefaultSchemes
	*/
	public function testUrlFilterCanBeConfiguredToAllowAdditionalSchemes()
	{
		// first we check that the regexp isn't borked and doesn't allow just about anything
		$filtersConfig = $this->cb->getFiltersConfig();
		$this->assertNotRegexp($filtersConfig['url']['allowedSchemes'], 'foo');

		$this->cb->allowScheme('foo');

		$filtersConfig = $this->cb->getFiltersConfig();
		$this->assertRegexp($filtersConfig['url']['allowedSchemes'], 'foo');
	}

	public function testUrlFilterCanBeConfiguredToDisallowHosts()
	{
		$this->cb->disallowHost('example.org');

		$filtersConfig = $this->cb->getFiltersConfig();

		$this->assertArrayHasNestedKeys(
			$filtersConfig,
			'url',
			'disallowedHosts'
		);

		$this->assertRegexp($filtersConfig['url']['disallowedHosts'], 'example.org');
	}

	public function testOptimizesRegexpByMergingHeads()
	{
		$this->assertSame(
			'ap(?:ple|ril)',
			ConfigBuilder::buildRegexpFromList(array('apple', 'april'))
		);
	}

	/**
	* @depends testOptimizesRegexpByMergingHeads
	*/
	public function testOptimizesRegexpByUsingCharacterClasses()
	{
		$this->assertSame(
			'ba[rz]',
			ConfigBuilder::buildRegexpFromList(array('bar', 'baz'))
		);
	}

	/**
	* @depends testOptimizesRegexpByMergingHeads
	*/
	public function testOptimizesRegexpByUsingQuantifier()
	{
		$this->assertSame(
			'fool?',
			ConfigBuilder::buildRegexpFromList(array('foo', 'fool'))
		);
	}

	/**
	* @depends testOptimizesRegexpByMergingHeads
	*/
	public function testOptimizesRegexpThatUsesWildcards()
	{
		$this->assertSame(
			'apple.*?',
			ConfigBuilder::buildRegexpFromList(
				array('apple*', 'applepie'),
				array('*' => '.*?')
			)
		);
	}

	/**
	* @depends testOptimizesRegexpByUsingCharacterClasses
	*/
	public function testOptimizesRegexpThatUsesParentheses()
	{
		$this->assertSame(
			'\\:[\\(\\)]',
			ConfigBuilder::buildRegexpFromList(array(':)', ':('))
		);
	}

	public function testOptimizesRegexpByUsingLookaheadAssertion()
	{
		$this->assertSame(
			'(?=[bf])(?:bar|foo)',
			ConfigBuilder::buildRegexpFromList(array('foo', 'bar'))
		);
	}

	/**
	* @depends testOptimizesRegexpByUsingLookaheadAssertion
	*/
	public function testOptimizesRegexpByUsingLookaheadAssertionWithEscapedCharacters()
	{
		$this->assertSame(
			'(?=[\\*\\\\])(?:\\*foo|\\\\bar)',
			ConfigBuilder::buildRegexpFromList(array('*foo', '\\bar'))
		);
	}

	/**
	* @depends testOptimizesRegexpByUsingLookaheadAssertion
	*/
	public function testDoesNotOptimizeRegexpByUsingLookaheadAssertionWithSpecialSequences()
	{
		$this->assertSame(
			'(?:.|bar)',

			// Here, we build a regexp that matches one single character or the word "bar"
			// The joker ? is replaced by the special character .
			ConfigBuilder::buildRegexpFromList(
				array('?', 'bar'),
				array('?' => '.')
			)
		);
	}

	/**
	* @test
	* @depends testCanCreateRule
	*/
	public function getParserConfig_flattens_allow_and_deny_rules_into_the_allow_array()
	{
		$this->cb->addTag('a', array('defaultRule' => 'allow'));
		$this->cb->addTag('b', array('defaultRule' => 'allow'));
		$this->cb->addTag('c', array('defaultRule' => 'deny'));

		$this->cb->addTagRule('a', 'deny', 'c');
		$this->cb->addTagRule('b', 'deny', 'a');
		$this->cb->addTagRule('c', 'allow', 'a');

		$this->assertArrayMatches(
			array(
				'tags' => array(
					'A' => array(
						'allow' => array(
							'A' => true,
							'B' => true
						)
					),
					'B' => array(
						'allow' => array(
							'B' => true,
							'C' => true
						)
					),
					'C' => array(
						'allow' => array(
							'A' => true
						)
					)
				)
			),
			$this->cb->getParserConfig()
		);
	}

	/**
	* @test
	* @depends testCanCreateRuleThatTargetsANonExistentTag
	*/
	public function getParserConfig_removes_rules_that_target_non_existing_tags()
	{
		$this->cb->addTag('a');

		$this->cb->addTagRule('a', 'closeParent', 'b');

		$this->assertArrayMatches(
			array(
				'tags' => array(
					'A' => array(
						// means there should NOT be a 'rules' key in this array
						'rules' => null
					)
				)
			),
			$this->cb->getParserConfig()
		);
	}

	/**
	* @test
	* @depends testCanCreateRuleThatTargetsANonExistentTag
	*/
	public function getParserConfig_preserves_requireParent_rules_that_target_non_existing_tags()
	{
		$this->cb->addTag('a');

		$this->cb->addTagRule('a', 'requireParent', 'b');

		$this->assertArrayMatches(
			array(
				'tags' => array(
					'A' => array(
						'rules' => array(
							'requireParent' => array(
								'B' => 'B'
							)
						)
					)
				)
			),
			$this->cb->getParserConfig()
		);
	}

	/**
	* @test
	* @depends testCanCreateRuleThatTargetsANonExistentTag
	*/
	public function getParserConfig_preserves_requireAscendant_rules_that_target_non_existing_tags()
	{
		$this->cb->addTag('a');

		$this->cb->addTagRule('a', 'requireAscendant', 'b');

		$this->assertArrayMatches(
			array(
				'tags' => array(
					'A' => array(
						'rules' => array(
							'requireAscendant' => array(
								'B' => 'B'
							)
						)
					)
				)
			),
			$this->cb->getParserConfig()
		);
	}

	/**
	* @test
	* @depends testLoadsPluginOnMagicGet
	*/
	public function getPluginsConfig_adds_default_config_if_missing()
	{
		$this->cb->Emoticons->addEmoticon(':)', '<img src="smile.png" />');

		$this->assertArrayMatches(
			array(
				'Emoticons' => array(
					'regexpLimit'       => $this->cb->Emoticons->regexpLimit,
					'regexpLimitAction' => $this->cb->Emoticons->regexpLimitAction
				)
			),
			$this->cb->getPluginsConfig()
		);
	}

	public function testPluginsCanBeDisabledByReturningFalseInsteadOfConfig()
	{
		$this->cb->loadPlugin('Emoticons');

		$this->assertSame(
			array(),
			$this->cb->getPluginsConfig()
		);
	}

	public function testCanAddGenericXsl()
	{
		$xsl = '<xsl:param name="foo"/>';
		$this->cb->addXSL($xsl);

		$this->assertContains(
			$xsl,
			$this->cb->getXSL()
		);
	}

	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Premature end of data in tag stylesheet line 1
	*/
	public function testCannotAddInvalidXsl()
	{
		$this->cb->addXSL('<lol>');
	}

	/**
	* @depends testCanCreateTagWithXsl
	*/
	public function testXslFromTagsAppearsInOutput()
	{
		$xsl = '<xsl:template match="A"><a/></xsl:template>';

		$this->cb->addTag('a', array(
			'xsl' => $xsl
		));

		$this->assertContains(
			$xsl,
			$this->cb->getXSL()
		);
	}

	/**
	* @test
	* @depends testCanCreateTag
	*/
	public function Can_add_a_preFilter_callback_to_a_tag()
	{
		$this->cb->addTag('a');
		$this->cb->addTagPreFilterCallback('a', 'trim');

		$this->assertArrayMatches(
			array(
				'A' => array(
					'preFilter' => array(
						array('callback' => 'trim')
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends testCanCreateTag
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Not a callback
	*/
	public function addTagPreFilterCallback_throws_an_exception_if_callback_is_not_callable()
	{
		$this->cb->addTag('a');
		$this->cb->addTagPreFilterCallback('a', 'uncallable');
	}

	/**
	* @test
	* @depends testCanCreateTag
	*/
	public function Can_set_preFilter_callbacks_via_setTagOption()
	{
		$this->cb->addTag('a');
		$this->cb->setTagOption('a', 'preFilter', array(
			array('callback' => 'trim')
		));

		$this->assertArrayMatches(
			array(
				'A' => array(
					'preFilter' => array(
						array('callback' => 'trim')
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends Can_add_a_preFilter_callback_to_a_tag
	*/
	public function Can_clear_all_preFilter_callbacks_from_a_tag()
	{
		$this->cb->addTag('a');
		$this->cb->addTagPreFilterCallback('a', 'trim');

		$this->cb->clearTagPreFilterCallbacks('a');

		$this->assertArrayMatches(
			array(
				'A' => array(
					'preFilter' => null
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends testCanCreateTag
	*/
	public function Can_add_a_postFilter_callback_to_a_tag()
	{
		$this->cb->addTag('a');
		$this->cb->addTagPostFilterCallback('a', 'trim');

		$this->assertArrayMatches(
			array(
				'A' => array(
					'postFilter' => array(
						array('callback' => 'trim')
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends testCanCreateTag
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Not a callback
	*/
	public function addTagPostFilterCallback_throws_an_exception_if_callback_is_not_callable()
	{
		$this->cb->addTag('a');
		$this->cb->addTagPostFilterCallback('a', 'uncallable');
	}

	/**
	* @test
	* @depends testCanCreateTag
	*/
	public function Can_set_postFilter_callbacks_via_setTagOption()
	{
		$this->cb->addTag('a');
		$this->cb->setTagOption('a', 'postFilter', array(
			array('callback' => 'trim')
		));

		$this->assertArrayMatches(
			array(
				'A' => array(
					'postFilter' => array(
						array('callback' => 'trim')
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends Can_add_a_postFilter_callback_to_a_tag
	*/
	public function Can_clear_all_postFilter_callbacks_from_a_tag()
	{
		$this->cb->addTag('a');
		$this->cb->addTagPostFilterCallback('a', 'trim');

		$this->cb->clearTagPostFilterCallbacks('a');

		$this->assertArrayMatches(
			array(
				'A' => array(
					'postFilter' => null
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends testCanCreateAttribute
	*/
	public function Can_add_a_preFilter_callback_to_a_tag_attribute()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'title', 'text');
		$this->cb->addTagAttributePreFilterCallback('a', 'title', 'trim');

		$this->assertArrayMatches(
			array(
				'A' => array(
					'attrs' => array(
						'title' => array(
							'preFilter' => array(
								array('callback' => 'trim')
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends testCanCreateAttribute
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Not a callback
	*/
	public function addTagAttributePreFilterCallback_throws_an_exception_if_callback_is_not_callable()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'title', 'text');
		$this->cb->addTagAttributePreFilterCallback('a', 'title', 'uncallable');
	}

	/**
	* @test
	* @depends testCanCreateAttribute
	*/
	public function Can_set_preFilter_callbacks_via_setTagAttributeOption()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'title', 'text');
		$this->cb->setTagAttributeOption('a', 'title', 'preFilter', array(
			array('callback' => 'trim')
		));

		$this->assertArrayMatches(
			array(
				'A' => array(
					'attrs' => array(
						'title' => array(
							'preFilter' => array(
								array('callback' => 'trim')
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends Can_add_a_preFilter_callback_to_a_tag_attribute
	*/
	public function Can_clear_all_preFilter_callbacks_from_a_tag_attribute()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'title', 'text');
		$this->cb->addTagAttributePreFilterCallback('a', 'title', 'trim');

		$this->cb->clearTagAttributePreFilterCallbacks('a', 'title');

		$this->assertArrayMatches(
			array(
				'A' => array(
					'attrs' => array(
						'title' => array(
							'preFilter' => null
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends testCanCreateAttribute
	*/
	public function Can_add_a_postFilter_callback_to_a_tag_attribute()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'title', 'text');
		$this->cb->addTagAttributePostFilterCallback('a', 'title', 'trim');

		$this->assertArrayMatches(
			array(
				'A' => array(
					'attrs' => array(
						'title' => array(
							'postFilter' => array(
								array('callback' => 'trim')
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends testCanCreateAttribute
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Not a callback
	*/
	public function addTagAttributePostFilterCallback_throws_an_exception_if_callback_is_not_callable()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'title', 'text');
		$this->cb->addTagAttributePostFilterCallback('a', 'title', 'uncallable');
	}

	/**
	* @test
	* @depends testCanCreateAttribute
	*/
	public function Can_set_postFilter_callbacks_via_setTagAttributeOption()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'title', 'text');
		$this->cb->setTagAttributeOption('a', 'title', 'postFilter', array(
			array('callback' => 'trim')
		));

		$this->assertArrayMatches(
			array(
				'A' => array(
					'attrs' => array(
						'title' => array(
							'postFilter' => array(
								array('callback' => 'trim')
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @test
	* @depends Can_add_a_postFilter_callback_to_a_tag_attribute
	*/
	public function Can_clear_all_postFilter_callbacks_from_a_tag_attribute()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'title', 'text');
		$this->cb->addTagAttributePostFilterCallback('a', 'title', 'trim');

		$this->cb->clearTagAttributePostFilterCallbacks('a', 'title');

		$this->assertArrayMatches(
			array(
				'A' => array(
					'attrs' => array(
						'title' => array(
							'postFilter' => null
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}
}