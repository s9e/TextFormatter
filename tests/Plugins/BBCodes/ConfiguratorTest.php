<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use DOMDocument;
use Exception;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically loads its default BBCode repository
	*/
	public function testDefaultRepository()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$this->assertTrue(isset($plugin->repositories['default']));
	}

	/**
	* @testdox addFromRepository('B') adds BBCode B and its tag from the default repository
	*/
	public function testAddFromDefaultRepository()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->addFromRepository('B');

		$this->assertTrue(isset($plugin->collection['B']));
		$this->assertTrue(isset($this->configurator->tags['B']));
	}

	/**
	* @testdox addFromRepository('MYBOLD', 'foo') adds BBCode B and its tag from the 'foo' repository
	*/
	public function testAddFromCustomRepository()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="MYBOLD">
					<usage>[MYBOLD]{TEXT}[/MYBOLD]</usage>
					<template><![CDATA[
						<b><xsl:apply-templates/></b>
					]]></template>
				</bbcode>
			</repository>'
		);

		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->repositories->add('foo', $dom);

		$plugin->addFromRepository('MYBOLD', 'foo');

		$this->assertTrue(isset($plugin->collection['MYBOLD']));
	}

	/**
	* @testdox addFromRepository('B', 'foo', ['title' => 'bar']) adds BBCode B and its tag from the 'foo' repository with variable 'title' replaced with content 'bar'
	*/
	public function testAddFromRepositoryWithVars()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="B">
					<usage>[B]{TEXT}[/B]</usage>
					<template><![CDATA[
						<b title="]]><var name="title"/><![CDATA["><xsl:apply-templates/></b>
					]]></template>
				</bbcode>
			</repository>'
		);

		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->repositories->add('foo', $dom);

		$plugin->addFromRepository('B', 'foo', ['title' => 'bar']);

		$this->assertSame(
			'<b title="bar"><xsl:apply-templates/></b>',
			(string) $this->configurator->tags['B']->defaultTemplate
		);
	}

	/**
	* @testdox addFromRepository('B', 'foo') throws an exception if repository 'foo' does not exist
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Repository 'foo' does not exist
	*/
	public function testAddFromUnknownRepository()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->addFromRepository('MYBOLD', 'foo');
	}

	/**
	* @testdox addFromRepository() throws an exception if the BBCode already exists
	* @expectedException RuntimeException
	* @expectedExceptionMessage BBCode 'B' already exists
	*/
	public function testAddFromRepositoryBBCodeExists()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->add('B');
		$plugin->addFromRepository('B');
	}

	/**
	* @testdox addFromRepository() throws an exception if the tag already exists
	* @expectedException RuntimeException
	* @expectedExceptionMessage Tag 'B' already exists
	*/
	public function testAddFromRepositoryTagExists()
	{
		$this->configurator->tags->add('B');
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->addFromRepository('B');
	}

	/**
	* @testdox addFromRepository() normalizes the tag's templates
	*/
	public function testAddFromRepositoryNormalize()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="X">
					<usage>[X]{TEXT}[/X]</usage>
					<template><![CDATA[
						<xsl:element name="hr"/>
					]]></template>
				</bbcode>
			</repository>'
		);

		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->repositories->add('foo', $dom);
		$plugin->addFromRepository('X', 'foo');

		$this->assertEquals(
			'<hr/>',
			$this->configurator->tags['X']->defaultTemplate
		);
	}

	/**
	* @testdox addFromRepository() checks that the tag is safe before adding it
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	*/
	public function testAddFromRepositoryCheckUnsafe()
	{
		$dom = new DOMDocument;
		$dom->loadXML(
			'<repository>
				<bbcode name="X">
					<usage>[X]{TEXT}[/X]</usage>
					<template><![CDATA[
						<img onerror="{TEXT}"/>
					]]></template>
				</bbcode>
			</repository>'
		);

		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->repositories->add('foo', $dom);

		try
		{
			$plugin->addFromRepository('X', 'foo');
		}
		catch (Exception $e)
		{
		}

		$this->assertFalse($this->configurator->tags->exists('X'));
		$this->assertFalse($plugin->exists('X'));

		if (isset($e))
		{
			throw $e;
		}
	}

	/**
	* @testdox addFromRepository() returns the newly-created BBCode
	*/
	public function testAddFromRepositoryReturn()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\BBCodes\\Configurator\\BBCode',
			$plugin->addFromRepository('B')
		);
	}

	/**
	* @testdox addCustom() returns the newly-created BBCode
	*/
	public function testAddCustom()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\BBCodes\\Configurator\\BBCode',
			$plugin->addCustom('[B]{TEXT}[/B]', '<b>{TEXT}</b>')
		);

		$this->assertTrue($this->configurator->tags->exists('B'));
	}

	/**
	* @testdox addCustom() accepts a single string as its second argument, representing the default template
	*/
	public function testAddCustomTemplate()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');

		$plugin->addCustom(
			'[B]{TEXT}[/B]',
			'<b>{TEXT}</b>'
		);

		$this->assertTrue($this->configurator->tags->exists('B'));
		$this->assertEquals(
			'<b><xsl:apply-templates/></b>',
			$this->configurator->tags['B']->templates->get('')
		);
	}

	/**
	* @testdox addCustom() accepts an array of [predicate => template] as its second argument
	*/
	public function testAddCustomTemplates()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');

		$plugin->addCustom(
			'[B]{TEXT}[/B]',
			[
				''     => '<b>{TEXT}</b>',
				'@foo' => '<strong>{TEXT}</strong>'
			]
		);

		$this->assertTrue($this->configurator->tags->exists('B'));
		$this->assertEquals(
			'<b><xsl:apply-templates/></b>',
			$this->configurator->tags['B']->templates->get('')
		);
		$this->assertEquals(
			'<strong><xsl:apply-templates/></strong>',
			$this->configurator->tags['B']->templates->get('@foo')
		);
	}

	/**
	* @testdox addCustom() normalizes the tag's template
	*/
	public function testAddCustomNormalize()
	{
		$this->configurator->BBCodes->addCustom('[X/]', '<xsl:element name="hr"/>');

		$this->assertEquals(
			'<hr/>',
			$this->configurator->tags['X']->defaultTemplate
		);
	}

	/**
	* @testdox addCustom() checks that the tag is safe before adding it
	* @expectedException s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException
	*/
	public function testAddCustomCheckUnsafe()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');

		try
		{
			$plugin->addCustom('[X]{TEXT}[/X]', '<img onerror="{TEXT}"/>');
		}
		catch (Exception $e)
		{
		}

		$this->assertFalse($this->configurator->tags->exists('X'));
		$this->assertFalse($plugin->exists('X'));

		if (isset($e))
		{
			throw $e;
		}
	}

	/**
	* @testdox asConfig() returns FALSE if no BBCodes were created
	*/
	public function testFalseConfig()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$this->assertFalse($plugin->asConfig());
	}

	/**
	* @testdox Has a quickMatch
	*/
	public function testConfigQuickMatch()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->add('B');

		$this->assertArrayHasKey(
			'quickMatch',
			$plugin->asConfig()
		);
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testRegexp()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->add('B');

		$this->assertArrayHasKey('regexp', $plugin->asConfig());
	}

	/**
	* @testdox The regexp that matches BBCode names does not contain a superfluous non-capturing subpattern
	*/
	public function testRegexpSubpatternRemoved()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->add('BAR');
		$plugin->add('FOO');

		$config = $plugin->asConfig();

		$this->assertNotContains('(?:', $config['regexp']);
	}

	/**
	* @testdox Essential non-capturing subpatterns are preserved
	*/
	public function testRegexpSubpatternPreserved()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->add('AAXXX');
		$plugin->add('AAYYY');
		$plugin->add('BBXXX');
		$plugin->add('BBYYY');

		$config = $plugin->asConfig();

		$this->assertContains('((?:AA|BB)(?:XXX|YYY))', $config['regexp']);
	}

	/**
	* @testdox asConfig() creates a JavaScript variant that preserves BBCode names
	*/
	public function testAsConfigPreservesBBCodeNames()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->add('FOO')->tagName = 'BAR';

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config, 'JS');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Dictionary',
			$config['bbcodes']
		);
		$this->assertArrayHasKey('FOO', $config['bbcodes']);
	}

	/**
	* @testdox asConfig() creates a JavaScript variant that preserves attribute names in predefinedAttributes
	*/
	public function testAsConfigPreservesPredefinedAttributeNames()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->add('FOO')->predefinedAttributes['k'] = 'v';

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config, 'JS');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Dictionary',
			$config['bbcodes']['FOO']['predefinedAttributes']
		);
		$this->assertArrayHasKey('k', $config['bbcodes']['FOO']['predefinedAttributes']);
	}
}