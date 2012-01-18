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
	// Used by some tests as a callback
	static public function filter()	{}

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
		$this->cb->addAttribute('a', 'href', 'url');

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
		$this->cb->addAttribute('a', 'href', 'url');
		$this->assertTrue($this->cb->attributeExists('a', 'href'));
	}

	/**
	* @depends testCanTellIfAttributeExists
	*/
	public function testCanRemoveAttributeIfItExists()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'href', 'url');
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
		$this->cb->addAttribute('foo', 'href', 'url');
	}

	/**
	* @depends testCanCreateAttribute
	*/
	public function testCanGetAttributeOptions()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'href', 'url', array('isRequired' => true));

		$this->assertArrayMatches(
			array('isRequired' => true),
			$this->cb->getAttributeOptions('a', 'href')
		);
	}

	/**
	* @depends testCanCreateAttribute
	*/
	public function testCanGetAttributeOption()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'href', 'url', array('isRequired' => true));

		$this->assertTrue($this->cb->getAttributeOption('a', 'href', 'isRequired'));
	}

	/**
	* @depends testCanGetAttributeOption
	*/
	public function testCanSetAttributeOption()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'href', 'url', array('isRequired' => true));

		$this->cb->setAttributeOption('a', 'href', 'isRequired', false);

		$this->assertFalse($this->cb->getAttributeOption('a', 'href', 'isRequired'));
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
		$this->cb->addAttribute('a', 'ns:href', 'url');
	}

	/**
	* @depends testCanTellIfAttributeExists
	*/
	public function testAttributeNamesCanContainHyphens()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'data--foo', 'text');
		$this->cb->attributeExists('a', 'data--foo');
	}

	/**
	* @depends testAttributeNamesCanContainHyphens
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid attribute name '-data--foo'
	*/
	public function testAttributeNamesMustStartWithALetterOrAnUnderscore()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', '-data--foo', 'text');
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
		$this->cb->addAttribute('a', 'href', 'url');
		$this->cb->addAttribute('a', 'href', 'text');
	}

	/**
	* @depends testCanCreateAttribute
	*/
	public function testDifferentTagsCanHaveAttributesOfTheSameName()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'href', 'url');

		$this->cb->addTag('link');
		$this->cb->addAttribute('link', 'href', 'url');
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
				// addAttribute where they are normalized
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

	public function testCanRegisterNamespaceWithValidPrefix()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');

		$this->assertSame(
			array('foo' => 'urn:foo'),
			$this->cb->getNamespaces()
		);
	}

	/**
	* @testdox Throws an exception if an attempt is made to register a namespace with the prefix 'xsl'
	* @depends testCanRegisterNamespaceWithValidPrefix
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage reserved
	*/
	public function testThrowsAnExceptionIfAnAttemptIsMadeToRegisterXSLPrefix()
	{
		$this->cb->registerNamespace('xsl', 'urn:foo');
	}

	/**
	* @testdox Throws an exception if an attempt is made to register a namespace with the URI 'http://www.w3.org/1999/XSL/Transform'
	* @depends testCanRegisterNamespaceWithValidPrefix
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage reserved
	*/
	public function testThrowsAnExceptionIfAnAttemptIsMadeToRegisterXSLURI()
	{
		$this->cb->registerNamespace('foo', 'http://www.w3.org/1999/XSL/Transform');
	}

	/**
	* @testdox Throws an exception if getXSL() is called with a prefix already in use
	* @depends testCanRegisterNamespaceWithValidPrefix
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Prefix 'foo' is already registered to namespace 'urn:foo'
	*/
	public function test_getXSL_with_prefix_already_in_use()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->getXSL('foo');
	}

	/**
	* @depends testCanRegisterNamespaceWithValidPrefix
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid prefix name 'foo>'
	*/
	public function testThrowsAnExceptionIfAnAttemptIsMadeToRegisterANamespaceWithAnInvalidPrefix()
	{
		$this->cb->registerNamespace('foo>', 'urn:foo');
	}

	/**
	* @depends testCanRegisterNamespaceWithValidPrefix
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Prefix 'foo' is already registered to namespace 'urn:foo'
	*/
	public function testThrowsAnExceptionIfAnAttemptIsMadeToOverwriteARegisteredNamespace()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->registerNamespace('foo', 'urn:bar');
	}

	/**
	* @depends testCanRegisterNamespaceWithValidPrefix
	*/
	public function testDoesNotThrowAnExceptionIfANamespaceIsRegisteredIdenticallyMultipleTimes()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->registerNamespace('foo', 'urn:foo');
	}

	/**
	* @depends testCanRegisterNamespaceWithValidPrefix
	*/
	public function testCanTellWhetherANamespaceHasBeenRegisteredToGivenPrefix()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');

		$this->assertTrue($this->cb->namespaceExists('foo'));
		$this->assertFalse($this->cb->namespaceExists('bar'));
	}

	/**
	* @depends testCanTellWhetherANamespaceHasBeenRegisteredToGivenPrefix
	*/
	public function testCanUnregisterANamespaceByItsPrefix()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->unregisterNamespace('foo');

		$this->assertFalse($this->cb->namespaceExists('foo'));
	}

	/**
	* @depends testCanRegisterNamespaceWithValidPrefix
	*/
	public function testCanReturnAListOfAllRegisteredNamespaces()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->registerNamespace('bar', 'urn:bar');

		$this->assertEquals(
			array('foo' => 'urn:foo', 'bar' => 'urn:bar'),
			$this->cb->getNamespaces()
		);
	}

	/**
	* @test
	* @depends testCanRegisterNamespaceWithValidPrefix
	*/
	public function Can_return_the_URI_associated_with_a_namespace_prefix()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');

		$this->assertSame('urn:foo', $this->cb->getNamespaceURI('foo'));
	}

	/**
	* @test
	* @depends testCanRegisterNamespaceWithValidPrefix
	*/
	public function Can_return_the_first_prefix_associated_with_a_namespace_URI()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');

		$this->assertSame('foo', $this->cb->getNamespacePrefix('urn:foo'));
	}

	/**
	* @test
	* @depends testCanRegisterNamespaceWithValidPrefix
	*/
	public function Can_return_all_the_prefixes_associated_with_a_namespace_URI()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->registerNamespace('foo2', 'urn:foo');

		$this->assertEquals(
			array('foo', 'foo2'),
			$this->cb->getNamespacePrefixes('urn:foo')
		);
	}

	/**
	* @depends testCanCreateTag
	* @depends testCanRegisterNamespaceWithValidPrefix
	*/
	public function testCanCreateTagInRegisteredNamespace()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->addTag('foo:bar');
	}

	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Namespace 'foo' is not registered
	*/
	public function testThrowsAnExceptionWhenCreatingATagWithAnUnregisteredNamespacePrefix()
	{
		$this->cb->addTag('foo:bar');
	}

	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name '*foo:bar'
	*/
	public function testThrowsAnExceptionWhenCreatingATagWithAnInvalidNamespacePrefix()
	{
		$this->cb->addTag('*foo:bar');
	}

	/**
	* @depends testCanCreateTagInRegisteredNamespace
	*/
	public function testCanTellIfNamespacedTagExists()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->addTag('foo:bar');

		$this->assertTrue($this->cb->tagExists('foo:bar'));
	}

	/**
	* @testdox Namespaced tag names are case-sensitive
	* @depends testCanTellIfNamespacedTagExists
	*/
	public function testNamespacedTagNamesAreCaseSensitive()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->addTag('foo:bar');

		$this->assertFalse($this->cb->tagExists('foo:BAR'));
	}

	/**
	* @depends testCanCreateTagInRegisteredNamespace
	*/
	public function testRegisteredNamespacesWithExistingTagsAppearInTheParserConfig()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->addTag('foo:bar');

		$this->assertArrayMatches(
			array(
				'namespaces' => array(
					'foo' => 'urn:foo'
				)
			),
			$this->cb->getParserConfig()
		);
	}

	/**
	* @depends testRegisteredNamespacesWithExistingTagsAppearInTheParserConfig
	*/
	public function testRegisteredNamespacesWithNoTagsDoNotAppearInTheParserConfig()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->assertArrayNotHasKey('namespaces', $this->cb->getParserConfig());
	}

	/**
	* @testdox Registered namespaces appear in the XSL
	*/
	public function testRegisteredNamespacesAppearInTheXSL()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->assertContains(
			'xmlns:foo="urn:foo"',
			$this->cb->getXSL()
		);
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
		$this->cb->setFilter('foo', 'trim');

		$this->assertArrayMatches(
			array(
				'foo' => array(
					'callback' => 'trim'
				)
			),
			$this->cb->getFiltersConfig()
		);
	}

	public function testCanSetCustomJavascriptFilter()
	{
		$this->cb->setJSFilter('foo', 'return "foo";');

		$this->assertArrayMatches(
			array(
				'foo' => array(
					'js' => 'return "foo";'
				)
			),
			$this->cb->getFiltersConfig(true)
		);
	}

	public function testCustomJavascriptFiltersDoNotAppearInTheParserConfig()
	{
		$this->cb->setJSFilter('url', 'return "foo";');

		$this->assertArrayMatches(
			array(
				'filters' => array(
					'url' => array(
						'js' => null
					)
				)
			),
			$this->cb->getParserConfig()
		);
	}

	/**
	* @depends testCanSetCustomFilter
	*/
	public function testCustomFiltersArePassedTheAttributeValueIfNoParamsArrayWasSpecified()
	{
		$this->cb->setFilter('foo', 'trim');

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
	public function testCanSetCustomFilterWithParameters()
	{
		$filterConf = array(
			'callback' => function($value, $min, $max) {},
			'params'   => array('attrVal'  => false, 2, 5)
		);

		$this->cb->setFilter('range', $filterConf['callback'], $filterConf['params']);

		$this->assertArrayMatches(
			array('range' => $filterConf),
			$this->cb->getFiltersConfig()
		);
	}

	/**
	* @test
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback 'does_not_exist' is not callable
	* @testdox setFilter() throws an exception on invalid callback
	*/
	public function setFilter_throws_an_exception_on_invalid_callback()
	{
		$this->cb->setFilter('foo', 'does_not_exist');
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

	/**
	* @testdox allowScheme() throws an exception on invalid scheme names
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid scheme name 'foo:bar'
	*/
	public function testInvalidSchemeNames()
	{
		$this->cb->allowScheme('foo:bar');
	}

	/**
	* @testdox There is no default scheme for schemeless URLs by default
	*/
	public function testThereIsNoDefaultSchemeForSchemelessURLsByDefault()
	{
		$filtersConfig = $this->cb->getFiltersConfig();

		$this->assertArrayNotHasKey('defaultScheme', $filtersConfig['url']);
	}

	/**
	* @testdox A default scheme to be used for URLs with no scheme can be set with setDefaultScheme()
	*/
	public function testADefaultSchemeCanBeSetForSchemelessURLs()
	{
		$this->cb->setDefaultScheme('http');
		$filtersConfig = $this->cb->getFiltersConfig();

		$this->assertArrayMatches(
			array(
				'url' => array(
					'defaultScheme' => 'http'
				)
			),
			$filtersConfig
		);
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
			'xn--pypal-4ve\\.com',
			$filtersConfig['url']['disallowedHosts']
		);
	}

	/**
	* @test
	* @depends testCanCreateRuleThatTargetsANonExistentTag
	* @testdox getParserConfig() removes rules that target non existing tags
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
	* @testdox getParserConfig() preserves requireParent rules that target non existing tags
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
	* @testdox getParserConfig() preserves requireAncestor rules that target non existing tags
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
	* @testdox getParserConfig() creates a "rootContext" entry with a hash for "allowedChildren" and "allowedDescendants"
	*/
	public function test_getParserConfig_rootContext()
	{
		$this->cb->addTag('a');

		$this->assertArrayMatches(
			array(
				'rootContext' => array(
					'allowedChildren'    => "\x01",
					'allowedDescendants' => "\x01"
				)
			),
			$this->cb->getParserConfig()
		);
	}

	/**
	* @test
	* @testdox getParserConfig() handles the "disallowAsRoot" option in the root context
	*/
	public function test_getParserConfig_rootContext_with_disallowAsRoot()
	{
		$this->cb->addTag('a', array('disallowAsRoot' => true));

		$this->assertArrayMatches(
			array(
				'rootContext' => array(
					'allowedChildren'    => "\x00",
					'allowedDescendants' => "\x01"
				)
			),
			$this->cb->getParserConfig()
		);
	}

	/**
	* @test
	* @depends testLoadsPluginOnMagicGet
	* @testdox getPluginsConfig() adds default config if missing
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
	* @testdox Can use a custom prefix for the XSL namespace
	*/
	public function testXSLPrefix()
	{
		$this->assertContains(
			'<xxx:stylesheet',
			$this->cb->getXSL('xxx')
		);
	}

	/**
	* @testdox Identical templates are merged
	*/
	public function testXSLDupes()
	{
		$this->cb->addTag('B',      array('template' => '<strong><xsl:apply-templates/></strong>'));
		$this->cb->addTag('BOLD',   array('template' => '<strong><xsl:apply-templates/></strong>'));
		$this->cb->addTag('STRONG', array('template' => '<strong><xsl:apply-templates/></strong>'));

		$this->assertXmlStringEqualsXmlString(
			'<?xml version="1.0" encoding="utf-8"?><xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"><xsl:output method="html" encoding="utf-8" indent="no"/><xsl:template match="/m"><xsl:for-each select="*"><xsl:apply-templates/><xsl:if test="following-sibling::*"><xsl:value-of select="/m/@uid"/></xsl:if></xsl:for-each></xsl:template><xsl:template match="B|BOLD|STRONG"><strong><xsl:apply-templates/></strong></xsl:template><xsl:template match="st|et|i"/></xsl:stylesheet>',
			$this->cb->getXSL()
		);
	}

	/**
	* @testdox Empty templates are merged with the default empty template
	*/
	public function testXSLEmptyDupes()
	{
		$this->cb->addTag('B',      array('template' => ''));

		$this->assertXmlStringEqualsXmlString(
			'<?xml version="1.0" encoding="utf-8"?><xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0"><xsl:output method="html" encoding="utf-8" indent="no"/><xsl:template match="/m"><xsl:for-each select="*"><xsl:apply-templates/><xsl:if test="following-sibling::*"><xsl:value-of select="/m/@uid"/></xsl:if></xsl:for-each></xsl:template><xsl:template match="B|st|et|i"/></xsl:stylesheet>',
			$this->cb->getXSL()
		);
	}

	/**
	* @testdox HTML attributes in templates are inlined
	*/
	public function testAttributesInTemplatesAreInlined()
	{
		$this->cb->addTag('B');
		$this->cb->setTagTemplate(
			'B',
			'<b><xsl:attribute name="title"><xsl:value-of select="@foo"/></xsl:attribute></b>'
		);

		$this->assertContains(
			'<xsl:template match="B"><b title="{@foo}"/></xsl:template>',
			$this->cb->getXSL()
		);
	}

	/**
	* @testdox Conditional attributes are optimized through <xsl:copy-of/>
	*/
	public function testConditionalAttributesAreOptimizedThroughCopy()
	{
		$this->cb->addTag('B');
		$this->cb->setTagTemplate(
			'B',
			'<b><xsl:if test="@foo"><xsl:attribute name="foo"><xsl:value-of select="@foo"/></xsl:attribute></xsl:if></b>'
		);

		$this->assertContains(
			'<xsl:template match="B"><b><xsl:copy-of select="@foo"/></b></xsl:template>',
			$this->cb->getXSL()
		);
	}

	/**
	* @testdox Simple templates in the form <b><xsl:apply-templates/></b> are merged together
	*/
	public function testSimpleTemplatesAreMerged()
	{
		$this->cb->addTag('B', array('template' => '<b><xsl:apply-templates/></b>'));
		$this->cb->addTag('I', array('template' => '<i><xsl:apply-templates/></i>'));
		$this->cb->addTag('U', array('template' => '<u><xsl:apply-templates/></u>'));

		$xsl = $this->cb->getXSL();

		$this->assertContains(
			'<xsl:template match="B|I|U"><xsl:element name="{translate(name(),\'BIU\',\'biu\')}"><xsl:apply-templates/></xsl:element></xsl:template>',
			$xsl
		);

		$this->assertNotContains(
			'<xsl:template match="B"><b><xsl:apply-templates/></b></xsl:template>',
			$xsl
		);
	}

	/**
	* @testdox Simple templates using an <xsl:apply-templates/> mode are not merged
	*/
	public function testNoApplyTemplatesModeInSimpleTemplates()
	{
		$this->cb->addTag('B', array('template' => '<b><xsl:apply-templates/></b>'));
		$this->cb->addTag('I', array('template' => '<i><xsl:apply-templates/></i>'));
		$this->cb->addTag('U', array('template' => '<u><xsl:apply-templates/></u>'));
		$this->cb->addTag('X', array('template' => '<x><xsl:apply-templates mode="foo" /></x>'));

		$this->assertContains(
			'<xsl:template match="B|I|U"><xsl:element name="{translate(name(),\'BIU\',\'biu\')}"><xsl:apply-templates/></xsl:element></xsl:template>',
			$this->cb->getXSL()
		);
	}

	/**
	* @testdox Simple templates with attributes are not merged
	*/
	public function testNoAttributesInSimpleTemplates()
	{
		$this->cb->addTag('B', array('template' => '<b class="foo"><xsl:apply-templates/></b>'));
		$this->cb->addTag('I', array('template' => '<i><xsl:apply-templates/></i>'));
		$this->cb->addTag('U', array('template' => '<u><xsl:apply-templates/></u>'));

		$this->assertContains(
			'<xsl:template match="B"><b class="foo"><xsl:apply-templates/></b></xsl:template>',
			$this->cb->getXSL()
		);
	}

	/**
	* @testdox Simple templates are not merged if there are less than 3 of them
	*/
	public function testNeedsThreeSimpleTemplates()
	{
		$this->cb->addTag('B', array('template' => '<b><xsl:apply-templates/></b>'));
		$this->cb->addTag('I', array('template' => '<i><xsl:apply-templates/></i>'));

		$this->assertContains(
			'<xsl:template match="B"><b><xsl:apply-templates/></b></xsl:template>',
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
	* @expectedExceptionMessage Callback 'uncallable' is not callable
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
	* @expectedExceptionMessage Callback 'uncallable' is not callable
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
	* @testdox setTagOption() accepts preFilter callbacks as an array of strings
	*/
	public function testTagPreFilterAsArrayOfStrings()
	{
		$this->cb->addTag('X', array('preFilter' => array('array_filter')));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'preFilter' => array(
						array('callback' => 'array_filter')
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox setTagOption() accepts a string for the "preFilter" option
	*/
	public function testTagPreFilterAsAString()
	{
		$this->cb->addTag('X', array('preFilter' => 'array_filter'));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'preFilter' => array(
						array('callback' => 'array_filter')
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox setTagOption() accepts postFilter callbacks as an array of strings
	*/
	public function testTagPostFilterAsArrayOfStrings()
	{
		$this->cb->addTag('X', array('postFilter' => array('array_filter')));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'postFilter' => array(
						array('callback' => 'array_filter')
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox setTagOption() accepts a string for the "postFilter" option
	*/
	public function testTagPostFilterAsAString()
	{
		$this->cb->addTag('X', array('postFilter' => 'array_filter'));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'postFilter' => array(
						array('callback' => 'array_filter')
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @depends testTagPreFilterAsAString
	* @testdox setTagOption() sets preFilter callbacks to receive the attributes array as sole parameter if params are not defined
	*/
	public function testTagPreFilterDefaultParams()
	{
		$this->cb->addTag('X', array('preFilter' => 'array_filter'));

		$this->assertEquals(
			array(
				array(
					'callback' => 'array_filter',
					'params'   => array('attrs' => null)
				)
			),
			$this->cb->getTagOption('X', 'preFilter')
		);
	}

	/**
	* @depends testTagPostFilterAsAString
	* @testdox setTagOption() sets postFilter callbacks to receive the attributes array as sole parameter if params are not defined
	*/
	public function testTagPostFilterDefaultParams()
	{
		$this->cb->addTag('X', array('postFilter' => 'array_filter'));

		$this->assertEquals(
			array(
				array(
					'callback' => 'array_filter',
					'params'   => array('attrs' => null)
				)
			),
			$this->cb->getTagOption('X', 'postFilter')
		);
	}

	/**
	* @test
	* @depends testCanCreateAttribute
	*/
	public function Can_add_a_preFilter_callback_to_a_tag_attribute()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'title', 'text');
		$this->cb->addAttributePreFilterCallback('a', 'title', 'trim');

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
	* @expectedExceptionMessage Callback 'uncallable' is not callable
	*/
	public function addAttributePreFilterCallback_throws_an_exception_if_callback_is_not_callable()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'title', 'text');
		$this->cb->addAttributePreFilterCallback('a', 'title', 'uncallable');
	}

	/**
	* @test
	* @depends testCanCreateAttribute
	*/
	public function Can_set_preFilter_callbacks_via_setAttributeOption()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'title', 'text');
		$this->cb->setAttributeOption('a', 'title', 'preFilter', array(
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
		$this->cb->addAttribute('a', 'title', 'text');
		$this->cb->addAttributePreFilterCallback('a', 'title', 'trim');

		$this->cb->clearAttributePreFilterCallbacks('a', 'title');

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
		$this->cb->addAttribute('a', 'title', 'text');
		$this->cb->addAttributePostFilterCallback('a', 'title', 'trim');

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
	* @expectedExceptionMessage Callback 'uncallable' is not callable
	*/
	public function addAttributePostFilterCallback_throws_an_exception_if_callback_is_not_callable()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'title', 'text');
		$this->cb->addAttributePostFilterCallback('a', 'title', 'uncallable');
	}

	/**
	* @test
	* @depends testCanCreateAttribute
	*/
	public function Can_set_postFilter_callbacks_via_setAttributeOption()
	{
		$this->cb->addTag('a');
		$this->cb->addAttribute('a', 'title', 'text');
		$this->cb->setAttributeOption('a', 'title', 'postFilter', array(
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
		$this->cb->addAttribute('a', 'title', 'text');
		$this->cb->addAttributePostFilterCallback('a', 'title', 'trim');

		$this->cb->clearAttributePostFilterCallbacks('a', 'title');

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
	* @testdox setAttributeOption() accepts preFilter callbacks as an array of strings
	*/
	public function testAttributePreFilterAsArrayOfStrings()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'preFilter' => array('strtolower', 'ucwords'))
			)
		));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'attrs' => array(
						'foo' => array(
							'preFilter' => array(
								array('callback' => 'strtolower'),
								array('callback' => 'ucwords')
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox setAttributeOption() accepts a string for the "preFilter" option
	*/
	public function testAttributePreFilterAsAString()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'preFilter' => 'strtolower')
			)
		));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'attrs' => array(
						'foo' => array(
							'preFilter' => array(
								array('callback' => 'strtolower')
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox setAttributeOption() accepts postFilter callbacks as an array of strings
	*/
	public function testAttributePostFilterAsArrayOfStrings()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'postFilter' => array('strtolower', 'ucwords'))
			)
		));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'attrs' => array(
						'foo' => array(
							'postFilter' => array(
								array('callback' => 'strtolower'),
								array('callback' => 'ucwords')
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox setAttributeOption() accepts a string for the "postFilter" option
	*/
	public function testAttributePostFilterAsAString()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'postFilter' => 'strtolower')
			)
		));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'attrs' => array(
						'foo' => array(
							'postFilter' => array(
								array('callback' => 'strtolower')
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox setAttributeOption() accepts preFilter callbacks as an array of callbacks
	*/
	public function testAttributePreFilterAsArrayOfCallbacks()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array(
					'type' => 'text',
					'preFilter' => array(
						array(__CLASS__, 'filter'),
						array(__CLASS__, 'filter')
					)
				)
			)
		));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'attrs' => array(
						'foo' => array(
							'preFilter' => array(
								array('callback' => array(__CLASS__, 'filter')),
								array('callback' => array(__CLASS__, 'filter'))
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox setAttributeOption() accepts postFilter callbacks as an array of callbacks
	*/
	public function testAttributePostFilterAsArrayOfCallbacks()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array(
					'type' => 'text',
					'postFilter' => array(
						array(__CLASS__, 'filter'),
						array(__CLASS__, 'filter')
					)
				)
			)
		));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'attrs' => array(
						'foo' => array(
							'postFilter' => array(
								array('callback' => array(__CLASS__, 'filter')),
								array('callback' => array(__CLASS__, 'filter'))
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox setAttributeOption() accepts a single callback for the "preFilter" option
	*/
	public function testAttributePreFilterAsACallback()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'preFilter' => array(__CLASS__, 'filter'))
			)
		));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'attrs' => array(
						'foo' => array(
							'preFilter' => array(
								array('callback' => array(__CLASS__, 'filter'))
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @testdox setAttributeOption() accepts a single callback for the "postFilter" option
	*/
	public function testAttributePostFilterAsACallback()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'postFilter' => array(__CLASS__, 'filter'))
			)
		));

		$this->assertArrayMatches(
			array(
				'X' => array(
					'attrs' => array(
						'foo' => array(
							'postFilter' => array(
								array('callback' => array(__CLASS__, 'filter'))
							)
						)
					)
				)
			),
			$this->cb->getTagsConfig()
		);
	}

	/**
	* @depends testAttributePreFilterAsAString
	* @testdox setAttributeOption() sets preFilter callbacks to receive the attribute's value as sole parameter if params are not defined
	*/
	public function testAttributePreFilterDefaultParams()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'preFilter' => 'strtolower')
			)
		));

		$this->assertEquals(
			array(
				array(
					'callback' => 'strtolower',
					'params'   => array('attrVal' => null)
				)
			),
			$this->cb->getAttributeOption('X', 'foo', 'preFilter')
		);
	}

	/**
	* @depends testAttributePostFilterAsAString
	* @testdox setAttributeOption() sets postFilter callbacks to receive the attribute's value as sole parameter if params are not defined
	*/
	public function testAttributePostFilterDefaultParams()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'postFilter' => 'strtolower')
			)
		));

		$this->assertEquals(
			array(
				array(
					'callback' => 'strtolower',
					'params'   => array('attrVal' => null)
				)
			),
			$this->cb->getAttributeOption('X', 'foo', 'postFilter')
		);
	}

	/**
	* @testdox setAttributeOption() throws an exception if a preFilter callback is not callable
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback 'foobar' is not callable
	*/
	public function testAttributePreFilterNotCallable()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'preFilter' => array('foobar'))
			)
		));
	}

	/**
	* @testdox setAttributeOption() throws an exception if a postFilter callback is not callable
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback 'foobar' is not callable
	*/
	public function testAttributePostFilterNotCallable()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'postFilter' => array('foobar'))
			)
		));
	}

	/**
	* @testdox setAttributeOption() throws an exception if a preFilter callback is not callable
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback config is missing the 'callback' key
	*/
	public function testAttributePreFilterMissingKey()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'preFilter' => array(array()))
			)
		));
	}

	/**
	* @testdox setAttributeOption() throws an exception if a postFilter callback is not callable
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Callback config is missing the 'callback' key
	*/
	public function testAttributePostFilterMissingKey()
	{
		$this->cb->addTag('X', array(
			'attrs' => array(
				'foo' => array('type' => 'text', 'postFilter' => array(array()))
			)
		));
	}

	/**
	* @testdox getJSParser() returns the Javascript source of the parser
	*/
	public function test_getJSParser()
	{
		$this->assertContains(
			's9e',
			$this->cb->getJSParser()
		);
	}

	/**
	* @testdox The array of option passed to getJSParser() is passed to the JS parser generator
	*/
	public function test_getJSParser_options()
	{
		$this->assertContains(
			's9e',
			$this->cb->getJSParser()
		);
	}

	/**
	* @test
	* @testdox getJSPlugins() returns an array of all plugins with a Javascript parser as well as their config and the associated metadata
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
	* @testdox getJSPlugins() ignores plugins with no Javascript parser
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

	/**
	* @testdox generateRulesFromHTML5Specs() uses the Renderer to generate a template for tags that have no template set
	* @depends testHTMLRules1
	*/
	public function testTheRendererIsUsedToGenerateATemplateForTagsThatHaveNoTemplateSet()
	{
		$this->cb->addTag('DIV');
		$this->cb->addTag('SPAN');

		$this->cb->addXSL(
			'<xsl:template match="DIV"><div><xsl:apply-templates/></div></xsl:template>
			<xsl:template match="SPAN"><span><xsl:apply-templates/></span></xsl:template>'
		);

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
	* @testdox getTagsConfig(true) removes options related to allowing tags and reduces them to two bitfields "allowedChildren" and "allowedDescendants"
	*/
	public function testAllowRulesAreReduced()
	{
		$this->cb->addTag('A', array(
			'defaultChildRule' => 'allow',
			'defaultDescendantRule' => 'allow'
		));

		$this->cb->addTagRule('A', 'denyChild', 'A');
		$this->cb->addTagRule('A', 'allowDescendant', 'A');

		$tagsConfig = $this->cb->getTagsConfig(true);

		$this->assertArrayNotHasKey('defaultChildRule', $tagsConfig['A']);
		$this->assertArrayNotHasKey('defaultDescendantRule', $tagsConfig['A']);
		$this->assertArrayHasKey('allowedChildren', $tagsConfig['A']);
		$this->assertArrayHasKey('allowedDescendants', $tagsConfig['A']);
		$this->assertSame("\x00", $tagsConfig['A']['allowedChildren']);
		$this->assertSame("\x01", $tagsConfig['A']['allowedDescendants']);
	}
}