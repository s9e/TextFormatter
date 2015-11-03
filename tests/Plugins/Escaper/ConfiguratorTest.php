<?php

namespace s9e\TextFormatter\Tests\Plugins\Escaper;

use s9e\TextFormatter\Plugins\Escaper\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Escaper\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox By default only escapes the characters !#()*+-.:@[\]^_`{}
	*/
	public function testDefaultEscapeSymbols()
	{
		$config = $this->configurator->plugins->load('Escaper')->asConfig();
		$regexp = $config['regexp'];
		$chars  = '!#()*+-.:<>@[\\]^_`{}';

		$i = 32;
		do
		{
			$c = chr($i);
			$method = (strpos($chars, $c) === false) ? 'assertNotRegExp' : 'assertRegExp';
			$this->$method($regexp, '\\' . $c);
		}
		while (++$i < 128);
	}

	/**
	* @testdox $plugin->escapeAll() makes it escape any Unicode character
	*/
	public function testCanEscapeAll()
	{
		$plugin = $this->configurator->plugins->load('Escaper');
		$plugin->escapeAll();

		$config = $plugin->asConfig();
		$regexp = $config['regexp'];

		$this->assertRegExp($regexp, '\\a');
		$this->assertRegexp($regexp, 'x\\♥x');

		preg_match($regexp, 'x\\♥x', $m);
		$this->assertSame('\\♥', $m[0]);
	}

	/**
	* @testdox $plugin->escapeAll(false) reverts to default escape list
	*/
	public function testCanEscapeAllFalse()
	{
		$plugin = $this->configurator->plugins->load('Escaper');

		$plugin->escapeAll();
		$config = $plugin->asConfig();
		$regexp = $config['regexp'];
		$this->assertRegExp($regexp, '\\a');

		$plugin->escapeAll(false);
		$config = $plugin->asConfig();
		$regexp = $config['regexp'];
		$this->assertNotRegExp($regexp, '\\a');
		$this->assertRegExp($regexp, '\\\\');
	}

	/**
	* @testdox Automatically creates an "ESC" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Escaper');
		$this->assertTrue($this->configurator->tags->exists('ESC'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Escaper', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox Has a quickMatch
	*/
	public function testConfigQuickMatch()
	{
		$this->assertArrayHasKey(
			'quickMatch',
			$this->configurator->plugins->load('Escaper')->asConfig()
		);
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testConfigRegexp()
	{
		$this->assertArrayHasKey(
			'regexp',
			$this->configurator->plugins->load('Escaper')->asConfig()
		);
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$this->assertArrayHasKey(
			'tagName',
			$this->configurator->plugins->load('Escaper')->asConfig()
		);
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('Escaper');

		$this->assertSame(
			$this->configurator->tags['ESC'],
			$plugin->getTag()
		);
	}
}