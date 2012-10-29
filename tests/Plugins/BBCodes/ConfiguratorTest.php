<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use DOMDocument;
use s9e\TextFormatter\Plugins\Autolink\Configurator;
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

		$plugin->addFromRepository('B', 'foo', array('title' => 'bar'));

		$this->assertSame(
			$this->configurator->tags['B']->defaultTemplate,
			'<b title="bar"><xsl:apply-templates/></b>'
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
	* @testdox addFromRepository() returns the newly-created BBCode
	*/
	public function testAddFromRepositoryReturn()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\BBCodes\\BBCode',
			$plugin->addFromRepository('B')
		);
	}

	/**
	* @testdox asConfig() returns FALSE if no BBCodes were created
	*/
	public function testFalseConfig()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$this->assertFalse($plugin->asConfig());
	}
}