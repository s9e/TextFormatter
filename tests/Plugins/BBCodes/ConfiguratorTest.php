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
	* @testdox $plugin->bbcodeMonkey is a publicly-accessible instance of s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeMonkey
	*/
	public function testBBCodeMonkeyPublic()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\BBCodes\\Configurator\\BBCodeMonkey',
			$this->configurator->BBCodes->bbcodeMonkey
		);
	}

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
			(string) $this->configurator->tags['B']->template
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
	* @testdox addFromRepository() respects onDuplicate() setting on tags
	*/
	public function testAddFromRepositoryDuplicateTag()
	{
		$this->configurator->tags->onDuplicate('ignore');
		$this->configurator->tags->add('B')->template = '...';
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->addFromRepository('B');
		$this->assertEquals('...', $this->configurator->tags['B']->template);
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
			$this->configurator->tags['X']->template
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
			$this->configurator->tags['B']->template
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
			$this->configurator->tags['X']->template
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
	* @testdox addCustom() accepts a custom "tagName" in options
	*/
	public function testAddCustomTagName()
	{
		$this->configurator->BBCodes->addCustom(
			'[*]{TEXT}[/*]',
			'<li>{TEXT}</li>',
			['tagName' => 'LI']
		);

		$this->assertTrue($this->configurator->tags->exists('LI'));
		$this->assertTrue($this->configurator->BBCodes->exists('*'));
	}

	/**
	* @testdox addCustom() accepts custom "rules" in options
	*/
	public function testAddCustomRules()
	{
		$this->configurator->BBCodes->addCustom(
			'[C]{TEXT}[/C]',
			'<code>{TEXT}</code>',
			['rules' => ['ignoreTags' => true]]
		);

		$this->assertTrue($this->configurator->tags->exists('C'));
		$this->assertTrue($this->configurator->BBCodes->exists('C'));
		$this->assertTrue($this->configurator->tags['C']->rules['ignoreTags']);
	}

	/**
	* @testdox asConfig() returns NULL if no BBCodes were created
	*/
	public function testNullConfig()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$this->assertNull($plugin->asConfig());
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
	* @testdox asConfig() returns BBCodes in a Dictionary
	*/
	public function testAsConfigPreservesBBCodeNames()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->add('FOO')->tagName = 'BAR';

		$config = ConfigHelper::filterConfig($plugin->asConfig(), 'JS');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Dictionary',
			$config['bbcodes']
		);
		$this->assertArrayHasKey('FOO', $config['bbcodes']);
	}

	/**
	* @testdox asConfig() returns predefinedAttributes in a Dictionary
	*/
	public function testAsConfigPreservesPredefinedAttributeNames()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->add('FOO')->predefinedAttributes['k'] = 'v';

		$config = ConfigHelper::filterConfig($plugin->asConfig(), 'JS');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Dictionary',
			$config['bbcodes']['FOO']['predefinedAttributes']
		);
		$this->assertArrayHasKey('k', $config['bbcodes']['FOO']['predefinedAttributes']);
	}
}