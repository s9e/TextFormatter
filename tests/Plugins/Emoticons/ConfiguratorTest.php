<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoticons;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\Emoticons\Configurator;

/**
* @covers s9e\TextFormatter\Plugins\Emoticons\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "E" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Emoticons');
		$this->assertTrue($this->configurator->tags->exists('E'));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('E');
		$this->configurator->plugins->load('Emoticons');

		$this->assertSame($tag, $this->configurator->tags->get('E'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Emoticons', array('tagName' => 'FOO'));
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox set(':)', '<img src="e.png"/>') creates a :) emoticon that maps to <img src="e.png"/>
	*/
	public function testSetXSL()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->set(':)', '<img src="e.png"/>');

		$xsl = $plugin->getXSL();

		$this->assertContains(':)', $xsl);
		$this->assertContains('<img src="e.png"/>', $xsl);
	}

	/**
	* @testdox set(':)', '<img src="e.png">') creates a :) emoticon that maps to <img src="e.png"/>
	*/
	public function testSetHTML()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->set(':)', '<img src="e.png">');

		$xsl = $plugin->getXSL();

		$this->assertContains(':)', $xsl);
		$this->assertContains('<img src="e.png"/>', $xsl);
	}

	/**
	* @testdox Emoticons can contain single quotes
	*/
	public function testSingleQuotes()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->set(":')", '<img src="e.png">');

		$this->assertSame(
			'<img src="e.png">',
			$this->renderSnippet('<E>:&#039;)</E>', $plugin->getXSL())
		);
	}

	/**
	* @testdox Emoticons can contain double quotes
	*/
	public function testDoubleQuotes()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->set('"_"', '<img src="e.png">');

		$this->assertSame(
			'<img src="e.png">',
			$this->renderSnippet('<E>&quot;_&quot;</E>', $plugin->getXSL())
		);
	}

	/**
	* @testdox Emoticons can contain both quotes at once
	*/
	public function testBothQuotes()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->set('\':")', '<img src="e.png">');

		$this->assertSame(
			'<img src="e.png">',
			$this->renderSnippet('<E>&#039;:&quot;)</E>', $plugin->getXSL())
		);
	}

	/**
	* @testdox asConfig() returns FALSE if no emoticons were set
	*/
	public function testFalseConfig()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$this->assertFalse($plugin->asConfig());
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testAsConfig()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->add(':)', ':)');

		$this->assertArrayHasKey('regexp', $plugin->asConfig());
	}
}