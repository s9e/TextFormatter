<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder,
    s9e\TextFormatter\PluginConfig;

include_once __DIR__ . '/Test.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder
*/
class ConfigBuilderTest extends Test
{
	public function testCanLoadPlugins()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\EmoticonsConfig',
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
			's9e\\TextFormatter\\Plugins\\EmoticonsConfig',
			$this->cb->Emoticons
		);
	}

	/**
	* @test
	*/
	public function Has_a_magic_property_predefinedTags_that_loads_and_creates_an_instance_of_PredefinedTags()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\PredefinedTags',
			$this->cb->predefinedTags
		);
	}

	/**
	* @expectedException RuntimeException
	* @expectedExceptionMessage Undefined property: s9e\TextFormatter\ConfigBuilder::$foo
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
	* @expectedExceptionMessage Class 's9e\TextFormatter\Plugins\FoobarConfig' not found
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
		$this->cb->addTagRule('FOO', 'denyChild', 'BAR');
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
		$this->cb->addTagRule('FOO', 'denyChild', 'BAR');
	}

	/**
	* @depends testCanCreateRule
	*/
	public function testCanRemoveRule()
	{
		$this->cb->addTag('a');
		$this->cb->addTag('b');
		$this->cb->addTagRule('a', 'allowChild', 'b');
		$this->cb->addTagRule('a', 'denyChild', 'b');
		$this->cb->addTagRule('a', 'allowChild', 'b');
		$this->cb->removeRule('a', 'allowChild', 'b');

		$tagsConfig = $this->cb->getTagsConfig();

		$expected = array(
			'A' => array(
				'rules' => array(
					'allowChild' => array(),

					'denyChild' => array(
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
				'allowChild' => array('B'),
				'denyChild'  => array('C', 'D')
			)
		));

		$expected = array(
			'A' => array(
				'rules' => array(
					'allowChild' => array(
						'B' => 'B'
					),
					'denyChild' => array(
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
	* @testdox Can set tag XSL
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
	* @testdox Cannot set tag template with invalid XSL
	* @depends testCanCreateTag
	* @expectedException InvalidArgumentException Invalid
	*/
	public function testCannotSetTagTemplateWithInvalidXsl()
	{
		$this->cb->addTag('br');
		$this->cb->setTagTemplate('br', '<br>');
	}

	/**
	* @testdox Cannot get tag XSL on unknown tag
	* @expectedException InvalidArgumentException FOO
	*/
	public function testCannotGetTagXslOnUnknownTag()
	{
		$this->cb->getTagXSL('FOO');
	}

	/**
	* @testdox Cannot get tag XSL on tag without XSL
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
	* @testdox Can create tag with XSL
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
	* @testdox Can set tag XSL
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
	* @expectedException RuntimeException ALLOW_UNSAFE_TEMPLATES
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
	* @depends testCanSetTagTemplate
	* @expectedException RuntimeException ALLOW_UNSAFE_TEMPLATES
	*/
	public function testCannotSetTagTemplateWithVariableInScriptSrcRegardlessOfTheCase()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<SCRIPT SRC="http://{TEXT}"/>'
		);
	}

	/**
	* @depends testCannotSetTagTemplateWithVariableInScriptSrc
	*/
	public function testCanSetTagTemplateWithVariableInScriptSrcWithUnsafeFlag()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<script src="http://{TEXT}"/>',
			ConfigBuilder::ALLOW_UNSAFE_TEMPLATES
		);
	}

	/**
	* @depends testCanSetTagTemplate
	* @expectedException RuntimeException ALLOW_UNSAFE_TEMPLATES
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
	public function testCanSetTagTemplateWithVariableInScriptContentWithUnsafeFlag()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<script><xsl:value-of select="@LOL"/></script>',
			ConfigBuilder::ALLOW_UNSAFE_TEMPLATES
		);
	}

	/**
	* @depends testCanSetTagTemplate
	* @expectedException RuntimeException ALLOW_UNSAFE_TEMPLATES
	*/
	public function testCannotSetTagTemplateWithVariableInScriptContentRegardlessOfTheCase()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<SCRIPT><xsl:value-of select="@LOL"/></SCRIPT>'
		);
	}

	/**
	* @depends testCanSetTagTemplate
	* @expectedException RuntimeException ALLOW_UNSAFE_TEMPLATES
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
	public function testCanSetTagTemplateWithDisableOutputEscapingWithUnsafeFlag()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<xsl:value-of select="@LOL" disable-output-escaping="yes" />',
			ConfigBuilder::ALLOW_UNSAFE_TEMPLATES
		);
	}

	/**
	* @depends testCanSetTagTemplate
	* @expectedException RuntimeException ALLOW_UNSAFE_TEMPLATES
	*/
	public function testCannotSetTagTemplateWithVariableInAnAttributeWhoseNameStartsWithOn()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<a onmouseover="{@lol}"/>'
		);
	}

	/**
	* @depends testCanSetTagTemplate
	* @expectedException RuntimeException ALLOW_UNSAFE_TEMPLATES
	*/
	public function testCannotSetTagTemplateWithVariableInAnAttributeWhoseNameStartsWithOnRegardlessOfTheCase()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<A ONMOUSEOVER="{@lol}"/>'
		);
	}

	/**
	* @depends testCanSetTagTemplate
	* @expectedException RuntimeException ALLOW_UNSAFE_TEMPLATES
	*/
	public function testCannotSetTagTemplateWithAnAttributeWhoseNameStartsWithOnCreatedViaXsl()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<a><xsl:attribute name="onmouseover"><xsl:value-of select="@lol"/></xsl:attribute></a>'
		);
	}

	/**
	* @depends testCannotSetTagTemplateWithVariableInAnAttributeWhoseNameStartsWithOn
	*/
	public function testCanSetTagTemplateWithVariableInAnAttributeWhoseNameStartsWithOnWithUnsafeFlag()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<a onmouseover="{@lol}"/>',
			ConfigBuilder::ALLOW_UNSAFE_TEMPLATES
		);
	}

	public function testCanSetTagTemplateWithEscapedCurlyBracketsInAnAttributeWhoseNameStartsWithOnWithoutUnsafeFlag()
	{
		$this->cb->addTag('a');
		$this->cb->setTagTemplate(
			'a',
			'<a onmouseover="a={{}}"/>'
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

	/**
	* @test
	*/
	public function Url_filter_can_be_configured_to_resolve_redirects_from_a_given_host()
	{
		$this->cb->resolveRedirectsFrom('bit.ly');

		$filtersConfig = $this->cb->getFiltersConfig();

		$this->assertArrayHasNestedKeys(
			$filtersConfig,
			'url',
			'resolveRedirectsHosts'
		);

		$this->assertRegexp($filtersConfig['url']['resolveRedirectsHosts'], 'bit.ly');
	}

	/**
	* @test
	*/
	public function Disallowed_IDNs_are_punycoded()
	{
		$this->cb->disallowHost('pаypal.com');

		$filtersConfig = $this->cb->getFiltersConfig();

		$this->assertArrayHasNestedKeys(
			$filtersConfig,
			'url',
			'disallowedHosts'
		);

		$this->assertContains(
			'xn\\-\\-pypal\\-4ve\\.com',
			$filtersConfig['url']['disallowedHosts']
		);
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
	* @testdox ConfigBuilder::parseRegexp() can parse plain regexps
	*/
	public function testCanParseRegexps1()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => 'foo',
				'tokens'    => array()
			),
			ConfigBuilder::parseRegexp(
				'#foo#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() throws a RuntimeException if delimiters can't be parsed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not parse regexp delimiters
	*/
	public function testInvalidRegexpsException1()
	{
		ConfigBuilder::parseRegexp('#foo/iD');
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses pattern modifiers
	*/
	public function testCanParseRegexps2()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => 'iD',
				'regexp'    => 'foo',
				'tokens'    => array()
			),
			ConfigBuilder::parseRegexp(
				'#foo#iD'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses character classes
	*/
	public function testCanParseRegexps3()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z]',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 5,
						'type' => 'characterClass',
						'content' => 'a-z',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#[a-z]#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses character classes with quantifiers
	*/
	public function testCanParseRegexps4()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z]+',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 6,
						'type' => 'characterClass',
						'content' => 'a-z',
						'quantifiers' => '+'
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#[a-z]+#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses character classes that end with an escaped ]
	*/
	public function testCanParseRegexps5()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '[a-z\\]]',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 7,
						'type' => 'characterClass',
						'content' => 'a-z\\]',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#[a-z\\]]#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() throws a RuntimeException if a character class is not properly closed
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find matching bracket from pos 0
	*/
	public function testInvalidRegexpsException2()
	{
		ConfigBuilder::parseRegexp('#[a-z)#');
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() correctly parses escaped brackets
	*/
	public function testCanParseRegexps6()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '\\[x\\]',
				'tokens'    => array()
			),
			ConfigBuilder::parseRegexp(
				'#\\[x\\]#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() correctly parses escaped parentheses
	*/
	public function testCanParseRegexps7()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '\\(x\\)',
				'tokens'    => array()
			),
			ConfigBuilder::parseRegexp(
				'#\\(x\\)#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses non-capturing subpatterns
	*/
	public function testCanParseRegexps8()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?:x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'options' => '',
						'endToken' => 1
					),
					array(
						'pos' => 5,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?:x+)#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses non-capturing subpatterns with atomic grouping
	*/
	public function testCanParseRegexps8b()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?>x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'subtype' => 'atomic',
						'endToken' => 1
					),
					array(
						'pos' => 5,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?>x+)#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses non-capturing subpatterns with quantifiers
	*/
	public function testCanParseRegexps9()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?:x+)++',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'nonCapturingSubpatternStart',
						'options' => '',
						'endToken' => 1
					),
					array(
						'pos' => 5,
						'len' => 3,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => '++'
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?:x+)++#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses non-capturing subpatterns with options
	*/
	public function testCanParseRegexps10()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?i:x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'nonCapturingSubpatternStart',
						'options' => 'i',
						'endToken' => 1
					),
					array(
						'pos' => 6,
						'len' => 1,
						'type' => 'nonCapturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?i:x+)#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses option settings
	*/
	public function testCanParseRegexps11()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?i)abc',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'option',
						'options' => 'i'
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?i)abc#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses named subpatterns using the (?<name>) syntax
	*/
	public function testCanParseRegexps12()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<foo>x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 7,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'endToken' => 1
					),
					array(
						'pos' => 9,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?<foo>x+)#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses named subpatterns using the (?P<name>) syntax
	*/
	public function testCanParseRegexps13()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?P<foo>x+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 8,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'endToken' => 1
					),
					array(
						'pos' => 10,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?P<foo>x+)#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses named subpatterns using the (?'name') syntax
	*/
	public function testCanParseRegexps14()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => "(?'foo'x+)",
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 7,
						'type' => 'capturingSubpatternStart',
						'name' => 'foo',
						'endToken' => 1
					),
					array(
						'pos' => 9,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				"#(?'foo'x+)#"
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses capturing subpatterns
	*/
	public function testCanParseRegexps15()
	{
		$this->assertEquals(
			array(
				'delimiter' => '/',
				'modifiers' => '',
				'regexp'    => '(x+)(abc\\d+)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 1,
						'type' => 'capturingSubpatternStart',
						'endToken' => 1
					),
					array(
						'pos' => 3,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					),
					array(
						'pos' => 4,
						'len' => 1,
						'type' => 'capturingSubpatternStart',
						'endToken' => 3
					),
					array(
						'pos' => 11,
						'len' => 1,
						'type' => 'capturingSubpatternEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'/(x+)(abc\\d+)/'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() throws a RuntimeException if an unmatched right parenthesis is found
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find matching pattern start for right parenthesis at pos 3
	*/
	public function testInvalidRegexpsException4()
	{
		ConfigBuilder::parseRegexp('#a-z)#');
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() throws a RuntimeException if an unmatched left parenthesis is found
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not find matching pattern end for left parenthesis at pos 0
	*/
	public function testInvalidRegexpsException5()
	{
		ConfigBuilder::parseRegexp('#(a-z#');
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() throws a RuntimeException on unsupported subpatterns
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unsupported subpattern type at pos 0
	*/
	public function testInvalidRegexpsUnsupportedSubpatternException()
	{
		ConfigBuilder::parseRegexp('#(?(condition)yes-pattern|no-pattern)#');
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses lookahead assertions
	*/
	public function testCanParseRegexps16()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?=foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'lookaheadAssertionStart',
						'endToken' => 1
					),
					array(
						'pos' => 6,
						'len' => 1,
						'type' => 'lookaheadAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?=foo)#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses negative lookahead assertions
	*/
	public function testCanParseRegexps17()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?!foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 3,
						'type' => 'negativeLookaheadAssertionStart',
						'endToken' => 1
					),
					array(
						'pos' => 6,
						'len' => 1,
						'type' => 'negativeLookaheadAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?!foo)#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses lookbehind assertions
	*/
	public function testCanParseRegexps18()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<=foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'lookbehindAssertionStart',
						'endToken' => 1
					),
					array(
						'pos' => 7,
						'len' => 1,
						'type' => 'lookbehindAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?<=foo)#'
			)
		);
	}

	/**
	* @testdox ConfigBuilder::parseRegexp() parses negative lookbehind assertions
	*/
	public function testCanParseRegexps19()
	{
		$this->assertEquals(
			array(
				'delimiter' => '#',
				'modifiers' => '',
				'regexp'    => '(?<!foo)',
				'tokens'    => array(
					array(
						'pos' => 0,
						'len' => 4,
						'type' => 'negativeLookbehindAssertionStart',
						'endToken' => 1
					),
					array(
						'pos' => 7,
						'len' => 1,
						'type' => 'negativeLookbehindAssertionEnd',
						'quantifiers' => ''
					)
				)
			),
			ConfigBuilder::parseRegexp(
				'#(?<!foo)#'
			)
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
	public function getParserConfig_preserves_requireAncestor_rules_that_target_non_existing_tags()
	{
		$this->cb->addTag('a');

		$this->cb->addTagRule('a', 'requireAncestor', 'b');

		$this->assertArrayMatches(
			array(
				'tags' => array(
					'A' => array(
						'rules' => array(
							'requireAncestor' => array(
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

	/**
	* @testdox Can add generic XSL
	*/
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
	* @testdox Cannot add invalid XSL
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Premature end of data in tag stylesheet line 1
	*/
	public function testCannotAddInvalidXsl()
	{
		$this->cb->addXSL('<lol>');
	}

	/**
	* @testdox XSL from tags appear in output
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
	* @testdox Can use a custom prefix for XSL namespace
	*/
	public function testXSLPrefix()
	{
		$this->assertContains(
			'<xxx:stylesheet',
			$this->cb->getXSL('xxx')
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

	/**
	* @test
	*/
	public function getJSPlugins_returns_an_array_of_all_plugins_with_a_Javascript_parser_as_well_as_their_config_and_the_associated_metadata()
	{
		include_once __DIR__ . '/includes/MyJsPluginConfig.php';

		$this->cb->loadPlugin('MyJsPlugin', __NAMESPACE__ . '\\MyJsPluginConfig');

		$this->assertEquals(
			array(
				'MyJsPlugin' => array(
					'config' => array('foo' => 'bar'),
					'meta'   => array('baz' => 'quux'),
					'parser' => 'alert("Hello mom")'
				)
			),
			$this->cb->getJSPlugins()
		);
	}

	/**
	* @test
	*/
	public function getJSPlugins_ignores_plugins_with_no_Javascript_parser()
	{
		include_once __DIR__ . '/includes/CannedConfig.php';

		$this->cb->loadPlugin('Canned', __NAMESPACE__ . '\\CannedConfig');

		$this->assertEquals(
			array(),
			$this->cb->getJSPlugins()
		);
	}

	/**
	* @testdox HTML specs: <span> does not allow <div> as a child
	*/
	public function testHTMLRules1()
	{
		$this->cb->addTag('DIV',  array('template' => '<div><xsl:apply-templates/></div>'));
		$this->cb->addTag('SPAN', array('template' => '<span><xsl:apply-templates/></span>'));

		$this->assertEquals(
			array(
				'DIV' => array(
					'rules' => array(
						'allowChild' => array('DIV', 'SPAN')
					)
				),
				'SPAN' => array(
					'rules' => array(
						'allowChild' => array('SPAN'),
						'denyChild'  => array('DIV')
					)
				)
			),
			$this->cb->generateRulesFromHTML5Specs()
		);
	}

	/**
	* @testdox HTML specs: <span> does not allow <div> as a child even with a <span> sibling
	*/
	public function testHTMLRules1b()
	{
		$this->cb->addTag('DIV', array(
			'template' => '<span>xxx</span><div><xsl:apply-templates/></div>'
		));
		$this->cb->addTag('SPAN', array('template' => '<span><xsl:apply-templates/></span>'));

		$this->assertEquals(
			array(
				'DIV' => array(
					'rules' => array(
						'allowChild' => array('DIV', 'SPAN')
					)
				),
				'SPAN' => array(
					'rules' => array(
						'allowChild' => array('SPAN'),
						'denyChild'  => array('DIV')
					)
				)
			),
			$this->cb->generateRulesFromHTML5Specs()
		);
	}

	/**
	* @testdox HTML specs: <span> with a <div> sibling does not allow <div> as a child
	*/
	public function testHTMLRules1c()
	{
		$this->cb->addTag('DIV', array(
			'template' => '<span><xsl:apply-templates/></span><div><xsl:apply-templates/></div>'
		));
		$this->cb->addTag('SPAN', array('template' => '<span><xsl:apply-templates/></span>'));

		$this->assertEquals(
			array(
				'DIV' => array(
					'rules' => array(
						'allowChild' => array('SPAN'),
						'denyChild'  => array('DIV')
					)
				),
				'SPAN' => array(
					'rules' => array(
						'allowChild' => array('SPAN'),
						'denyChild'  => array('DIV')
					)
				)
			),
			$this->cb->generateRulesFromHTML5Specs()
		);
	}

	/**
	* @testdox HTML specs: <li> closes parent <li>
	*/
	public function testHTMLRules2()
	{
		$this->cb->addTag('LI', array('template' => '<li><xsl:apply-templates/></li>'));
		$this->cb->addTag('UL', array('template' => '<ul><xsl:apply-templates/></ul>'));

		$this->assertEquals(
			array(
				'LI' => array(
					'rules' => array(
						'allowChild'  => array('UL'),
						'denyChild'   => array('LI'),
						'closeParent' => array('LI')
					)
				),
				'UL' => array(
					'rules' => array(
						'allowChild' => array('LI'),
						'denyChild'  => array('UL')
					)
				)
			),
			$this->cb->generateRulesFromHTML5Specs()
		);
	}

	/**
	* @testdox HTML specs: <div> or <p> closes parent <p>
	*/
	public function testHTMLRules3()
	{
		$this->cb->addTag('DIV', array('template' => '<div><xsl:apply-templates/></div>'));
		$this->cb->addTag('P',   array('template' => '<p><xsl:apply-templates/></p>'));

		$this->assertEquals(
			array(
				'DIV' => array(
					'rules' => array(
						'allowChild'  => array('DIV', 'P'),
						'closeParent' => array('P')
					)
				),
				'P' => array(
					'rules' => array(
						'denyChild'   => array('DIV', 'P'),
						'closeParent' => array('P')
					)
				)
			),
			$this->cb->generateRulesFromHTML5Specs()
		);
	}

	/**
	* @testdox HTML specs: <a> denies <a> as descendant
	*/
	public function testHTMLRules4()
	{
		$this->cb->addTag('A', array('template' => '<a><xsl:apply-templates/></a>'));

		$this->assertEquals(
			array(
				'A' => array(
					'isTransparent' => true,
					'rules' => array(
						'denyDescendant' => array('A')
					)
				)
			),
			$this->cb->generateRulesFromHTML5Specs()
		);
	}

	/**
	* @testdox HTML specs: <a> allows <img> with no usemap attribute
	*/
	public function testHTMLRules5()
	{
		$this->cb->addTag('A', array('template' => '<a><xsl:apply-templates/></a>'));
		$this->cb->addTag('IMG', array('template' => '<img/>'));

		$this->assertEquals(
			array(
				'A' => array(
					'isTransparent' => true,
					'rules' => array(
						'denyDescendant' => array('A'),
						'allowChild' => array('IMG')
					)
				),
				'IMG' => array(
					'rules' => array(
						'denyChild' => array('A', 'IMG')
					)
				)
			),
			$this->cb->generateRulesFromHTML5Specs()
		);
	}

	/**
	* @testdox HTML specs: <a> denies <img> with usemap attribute
	*/
	public function testHTMLRules6()
	{
		$this->cb->addTag('A', array('template' => '<a><xsl:apply-templates/></a>'));
		$this->cb->addTag('IMG', array('template' => '<img usemap="#foo"/>'));

		$this->assertEquals(
			array(
				'A' => array(
					'isTransparent' => true,
					'rules' => array(
						'denyDescendant' => array('A', 'IMG')
					)
				),
				'IMG' => array(
					'rules' => array(
						'denyChild' => array('A', 'IMG')
					)
				)
			),
			$this->cb->generateRulesFromHTML5Specs()
		);
	}

	/**
	* @testdox HTML specs: <div><a> allows <div>
	*/
	public function testHTMLRules7a()
	{
		$this->cb->addTag('A', array('template' => '<a><xsl:apply-templates/></a>'));
		$this->cb->addTag('DIV', array('template' => '<div><xsl:apply-templates/></div>'));

		$this->assertEquals(
			array(
				'A' => array(
					'isTransparent' => true,
					'rules' => array(
						'allowChild' => array('DIV'),
						'denyDescendant' => array('A')
					)
				),
				'DIV' => array(
					'rules' => array(
						'allowChild' => array('A', 'DIV'),
					)
				)
			),
			$this->cb->generateRulesFromHTML5Specs()
		);
	}

	/**
	* @testdox HTML specs: <span><a> denies <div>
	*/
	public function testHTMLRules7b()
	{
		$this->cb->addTag('A', array('template' => '<a><xsl:apply-templates/></a>'));
		$this->cb->addTag('DIV', array('template' => '<div><xsl:apply-templates/></div>'));
		$this->cb->addTag('SPAN', array('template' => '<span><xsl:apply-templates/></span>'));

		$this->assertEquals(
			array(
				'A' => array(
					'isTransparent' => true,
					'rules' => array(
						'allowChild' => array('DIV', 'SPAN'),
						'denyDescendant' => array('A')
					)
				),
				'DIV' => array(
					'rules' => array(
						'allowChild' => array('A', 'DIV', 'SPAN')
					)
				),
				'SPAN' => array(
					'rules' => array(
						'allowChild' => array('A', 'SPAN'),
						'denyChild' => array('DIV')
					)
				)
			),
			$this->cb->generateRulesFromHTML5Specs()
		);
	}

	/**
	* @depends testHTMLRules1
	* @testdox addRulesFromHTML5Specs() creates rules based on HTML5 specs
	*/
	public function testAddRulesAddsRules()
	{
		$this->cb->addTag('DIV',  array('template' => '<div><xsl:apply-templates/></div>'));
		$this->cb->addTag('SPAN', array('template' => '<span><xsl:apply-templates/></span>'));

		$this->cb->addRulesFromHTML5Specs();

		$this->assertArrayMatches(
			array(
				'DIV' => array(
					'rules' => array(
						'allowChild' => array('DIV' => 'DIV', 'SPAN' => 'SPAN')
					)
				),
				'SPAN' => array(
					'rules' => array(
						'allowChild' => array('SPAN' => 'SPAN'),
						'denyChild'  => array('DIV' => 'DIV')
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @depends testHTMLRules7a
	* @testdox addRulesFromHTML5Specs() sets options based on HTML5 specs
	*/
	public function testAddRulesSetsOptions()
	{
		$this->cb->addTag('A', array('template' => '<a><xsl:apply-templates/></a>'));
		$this->cb->addTag('DIV', array('template' => '<div><xsl:apply-templates/></div>'));

		$this->cb->addRulesFromHTML5Specs();

		$this->assertArrayMatches(
			array(
				'A' => array(
					'isTransparent' => true
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox Tags with option 'disable' are not returned by getTagsConfig(true)
	*/
	public function testDisabledTagsDoNotAppearInConfig()
	{
		$this->cb->addTag('A');
		$this->cb->addTag('B', array('disable' => true));
		$this->cb->addTag('C');

		$tagsConfig = $this->cb->getTagsConfig(true);

		$this->assertArrayHasKey('A', $tagsConfig);
		$this->assertArrayNotHasKey('B', $tagsConfig);
		$this->assertArrayHasKey('C', $tagsConfig);
	}

	/**
	* @testdox addRulesFromHTML5Specs(array('rootElement' => 'div')) prevents <li> to be used at root
	*/
	public function testAddRulesSetsDisallowAsRoot()
	{
		$this->cb->addTag('DIV', array('template' => '<div><xsl:apply-templates/></div>'));
		$this->cb->addTag('LI',  array('template' => '<li><xsl:apply-templates/></li>'));

		$this->cb->addRulesFromHTML5Specs(array('rootElement' => 'div'));

		$this->assertArrayMatches(
			array(
				'DIV' => array(
					'disallowAsRoot' => false
				),
				'LI' => array(
					'disallowAsRoot' => true
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox addRulesFromHTML5Specs(array('rootElement' => 'a')) disables tags that contain a <a>
	*/
	public function testAddRulesDisablesDeniedDescendants()
	{
		$this->cb->addTag('A', array('template' => '<a><xsl:apply-templates/></a>'));

		$this->cb->addRulesFromHTML5Specs(array('rootElement' => 'a'));

		$this->assertArrayMatches(
			array(
				'A' => array(
					'disable' => true
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox generateRulesFromHTML5Specs(array('rootElement' => 'xxx')) throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Unknown HTML element 'xxx'
	*/
	public function testGenerateHTML5RulesThrowsAnExceptionOnUnknownRootElement()
	{
		$this->cb->generateRulesFromHTML5Specs(array('rootElement' => 'xxx'));
	}
}