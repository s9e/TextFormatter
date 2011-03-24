<?php

namespace s9e\Toolkit\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer,
    s9e\Toolkit\TextFormatter\Plugins\EmoticonsConfig;

include_once __DIR__ . '/../../src/TextFormatter/ConfigBuilder.php';
include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\ConfigBuilder
*/
class ConfigBuilderTest extends Test
{
	public function setUp()
	{
		$this->cb = new ConfigBuilder;
	}

	/**
	* @runInSeparateProcess
	*/
	public function testLoadsPluginOnMagicGet()
	{
		$this->assertTrue($this->cb->Emoticons instanceof EmoticonsConfig);
	}

	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid plugin name "../foo"
	*/
	public function testDoesNotLoadInvalidPluginName()
	{
		$this->cb->loadPlugin('../foo');
	}

	/**
	* @runInSeparateProcess
	*/
	public function testCanAutoloadParser()
	{
		$this->assertTrue($this->cb->getParser() instanceof Parser);
	}

	/**
	* @runInSeparateProcess
	* @depends testCanAutoloadParser
	*/
	public function testDoesNotIncludeParserTwice()
	{
		$this->assertTrue($this->cb->getParser() instanceof Parser);
		$this->assertTrue($this->cb->getParser() instanceof Parser);
	}

	/**
	* @runInSeparateProcess
	*/
	public function testCanAutoloadRenderer()
	{
		$this->assertTrue($this->cb->getRenderer() instanceof Renderer);
	}

	/**
	* @runInSeparateProcess
	* @depends testCanAutoloadRenderer
	*/
	public function testDoesNotIncludeRendererTwice()
	{
		$this->assertTrue($this->cb->getRenderer() instanceof Renderer);
		$this->assertTrue($this->cb->getRenderer() instanceof Renderer);
	}

	public function testCanCreateTag()
	{
		$this->cb->addTag('a');
		$this->assertArrayHasKey('A', $this->cb->getTagsConfig());
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
	public function testCanRemoveAttribute()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('a', 'href', 'url');
		$this->cb->removeAttribute('a', 'href');
		$this->assertFalse($this->cb->attributeExists('a', 'href'));
	}

	/**
	* @depends testCanCreateAttribute
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Unknown tag 'FOO'
	*/
	public function testCannotCreateAttributeOnNonExistingTag()
	{
		$this->cb->addTag('a');
		$this->cb->addTagAttribute('foo', 'href', 'url');
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
	* @expectedExceptionMessage Unknown tag 'FOO'
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
	* @depends testCanCreateRule
	* @expectedException RuntimeException
	* @expectedExceptionMessage Tag 'FOO' already has a different 'requireParent' rule
	*/
	public function testCannotCreateMultipeRequireParentRulesOnDifferentTargets()
	{
		$this->cb->addTag('FOO');
		$this->cb->addTag('BAR');
		$this->cb->addTag('BAZ');
		$this->cb->addTagRule('FOO', 'requireParent', 'BAR');
		$this->cb->addTagRule('FOO', 'requireParent', 'BAZ');
	}

	/**
	* @depends testCanCreateRule
	*/
	public function testCanCreateMultipeRequireParentRulesOnSameTarget()
	{
		$this->cb->addTag('FOO');
		$this->cb->addTag('BAR');
		$this->cb->addTagRule('FOO', 'requireParent', 'BAR');
		$this->cb->addTagRule('FOO', 'requireParent', 'BAR');
	}

	/**
	* @depends testCanCreateTag
	*/
	public function testCanCreateRuleOnNonExistentTargetTag()
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
	public function testTagOptionsPHPTypeIsPreserved()
	{
		$this->cb->addTag('a', array('nestingLimit' => '123'));

		$this->assertSame(123, $this->cb->getTagOption('a', 'nestingLimit'));
	}

	/**
	* @expectedException InvalidArgumentException FOO
	*/
	public function testGetTagOptionsFailsOnUnknownTags()
	{
		$this->cb->getTagOptions('foo');
	}

	/**
	* @expectedException InvalidArgumentException FOO
	*/
	public function testGetTagOptionFailsOnUnknownTags()
	{
		$this->cb->getTagOption('foo', 'nestingLimit');
	}

	/**
	* @depends testCanCreateTag
	* @expectedException InvalidArgumentException bar
	*/
	public function testGetTagOptionFailsOnUnsetOptions()
	{
		$this->cb->addTag('a');
		$this->cb->getTagOption('a', 'bar');
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
	public function testCanSetTagXSL()
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
	public function testCannotSetTagTemplateWithInvalidXSL()
	{
		$this->cb->addTag('br');
		$this->cb->setTagTemplate('br', '<br>');
	}

	/**
	* @expectedException InvalidArgumentException FOO
	*/
	public function testCannotGetTagXSLOnUnknownTag()
	{
		$this->cb->getTagXSL('FOO');
	}

	/**
	* @depends testCanCreateTag
	* @expectedException InvalidArgumentException No
	*/
	public function testCannotGetTagXSLOnTagWithoutXSL()
	{
		$this->cb->addTag('FOO');
		$this->cb->getTagXSL('FOO');
	}

	/**
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
	* @depends testCanSetTagXSL
	*/
	public function testCanCreateTagWithXSL()
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
	* @depends testCanSetTagXSL
	*/
	public function testCanSetTagXSLWithPreservedWhitespace()
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

	public function testCanSetCustomFilter()
	{
		$this->cb->setFilter('text', array('callback' => 'trim'));

		$this->assertArrayMatches(
			array(
				'text' => array(
					'callback' => 'trim'
				)
			),
			$this->cb->getFiltersConfig()
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
	* @expectedException InvalidArgumentException callback
	*/
	public function testSetFilterThrowsAnExceptionOnInvalidCallback()
	{
		$this->cb->setFilter('foo', array('callback' => 'bar'));
	}

	public function testUrlFilterIsConfiguredToAllowHttpAndHttpsSchemesByDefault()
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
	* @depends testUrlFilterIsConfiguredToAllowHttpAndHttpsSchemesByDefault
	*/
	public function testUrlFilterCanBeConfiguredAllowAdditionalSchemes()
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
	* @depends testOptimizesRegexpByMergingHeads
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
	* @depends testCanCreateRule
	*/
	public function testGetParserConfigFlattensAllowAndDenyRulesIntoTheAllowArray()
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
	* @depends testCanCreateRule
	* @depends testCanRemoveTag
	*/
	public function testGetParserConfigRemovesRulesThatPertainToNonExistingTags()
	{
		$this->cb->addTag('a');
		$this->cb->addTag('b');
		$this->cb->addTag('c');

		$this->cb->addTagRule('a', 'closeParent', 'c');
		$this->cb->addTagRule('b', 'closeParent', 'a');

		$this->cb->removeTag('c');

		$this->assertArrayMatches(
			array(
				'tags' => array(
					'A' => array(
						// means there should NOT be a 'rules' key in this array
						'rules' => null
					),
					'B' => array(
						'rules' => array(
							'closeParent' => array('A')
						)
					)
				)
			),
			$this->cb->getParserConfig()
		);
	}

	/**
	* @depends testCanCreateRule
	* @depends testCanRemoveTag
	*/
	public function testGetParserConfigKeepsRequireParentRuleAsAString()
	{
		$this->cb->addTag('a');
		$this->cb->addTag('b');

		$this->cb->addTagRule('a', 'requireParent', 'b');

		$this->assertArrayMatches(
			array(
				'tags' => array(
					'A' => array(
						'rules' => array(
							'requireParent' => 'B'
						)
					)
				)
			),
			$this->cb->getParserConfig()
		);
	}

	/**
	* @depends testLoadsPluginOnMagicGet
	*/
	public function testGetPluginsConfigAddsDefaultConfigIfMissing()
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

	public function testCanAddGenericXSL()
	{
		$xsl = '<xsl:param name="foo"/>';
		$this->cb->addXSL($xsl);

		$this->assertContains(
			$xsl,
			$this->cb->getXSL()
		);
	}

	/**
	* @expectedException InvalidArgumentException Premature
	*/
	public function testCannotAddInvalidXSL()
	{
		$this->cb->addXSL('<lol>');
	}

	/**
	* @depends testCanCreateTagWithXSL
	*/
	public function testXSLFromTagsAppearsInOutput()
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
}