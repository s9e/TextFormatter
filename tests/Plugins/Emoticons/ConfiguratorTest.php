<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoticons;

use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Plugins\Emoticons\Configurator;
use s9e\TextFormatter\Tests\Test;

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
		$this->configurator->plugins->load('Emoticons', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox set(':)', '<img src="e.png"/>') creates a :) emoticon that maps to <img src="e.png"/>
	*/
	public function testSetXSL()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->set(':)', '<img src="e.png"/>');

		$xsl = $plugin->getTemplate();

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

		$xsl = $plugin->getTemplate();

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

		$xsl = $plugin->getTemplate();

		$this->assertContains(":')", $xsl);
		$this->assertContains('<img src="e.png"/>', $xsl);
	}

	/**
	* @testdox Emoticons can contain double quotes
	*/
	public function testDoubleQuotes()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->set('"_"', '<img src="e.png">');

		$xsl = $plugin->getTemplate();

		$this->assertContains("'&quot;_&quot;'", $xsl);
		$this->assertContains('<img src="e.png"/>', $xsl);
	}

	/**
	* @testdox Emoticons can contain both quotes at once
	*/
	public function testBothQuotes()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->set('\':")', '<img src="e.png">');

		$xsl = $plugin->getTemplate();

		$this->assertContains('<img src="e.png"/>', $xsl);
	}

	/**
	* @testdox asConfig() returns NULL if no emoticons were set
	*/
	public function testNullConfig()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$this->assertNull($plugin->asConfig());
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

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$plugin = $this->configurator->plugins->load('Emoticons', ['tagName' => 'FOO']);
		$plugin->add(':)', ':)');

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('FOO', $config['tagName']);
	}

	/**
	* @testdox asConfig() generates a quickMatch if applicable
	*/
	public function testConfigQuickMatch()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->add(':)', ':)');
		$plugin->add(':(', ':(');

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('quickMatch', $config);
		$this->assertSame(':', $config['quickMatch']);
	}

	/**
	* @testdox $plugin->notAfter can be changed
	*/
	public function testNotAfter()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->add('x', 'x');
		$plugin->notAfter = '\\w';

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertSame('/(?<!\\w)x/S', $config['regexp']);
	}

	/**
	* @testdox The plugin's modified JavaScript regexp is correctly converted
	*/
	public function testNotAfterPluginJavaScriptConversion()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->add('xx', 'xx');
		$plugin->add('yy', 'yy');
		$plugin->notAfter = '\\w';

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config, 'JS');

		$this->assertEquals(new Code('/(?:xx|yy)/g'), $config['regexp']);
		$this->assertEquals(new Code('/\\w/'),        $config['notAfter']);
	}

	/**
	* @testdox The JavaScript regexp used for notAfter is correctly converted
	*/
	public function testNotAfterJavaScriptConversion()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->add('x', 'x');
		$plugin->notAfter = '(?>x)';

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config, 'JS');

		$this->assertEquals(new Code('/x/g'),    $config['regexp']);
		$this->assertEquals(new Code('/(?:x)/'), $config['notAfter']);
	}

	/**
	* @testdox $plugin->notBefore can be changed
	*/
	public function testNotBefore()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->add('x', 'x');
		$plugin->notBefore = '\\w';

		$config = $plugin->asConfig();

		$this->assertSame('/x(?!\\w)/S', $config['regexp']);
	}

	/**
	* @testdox $plugin->notAfter is removed from the JavaScript regexp and added separately to the config
	*/
	public function testNotAfterJavaScript()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->add('x', 'x');
		$plugin->notAfter = '\\w';

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config, 'JS');

		$this->assertEquals(new Code('/x/g', 'g'), $config['regexp']);
		$this->assertEquals(new Code('/\\w/'),     $config['notAfter']);
	}

	/**
	* @testdox The regexp has the Unicode modifier if notAfter contains a Unicode property
	*/
	public function testNotAfterUnicode()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->add('x', 'x');
		$plugin->notAfter = '\\pL';

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertSame('/(?<!\\pL)x/Su', $config['regexp']);
	}

	/**
	* @testdox The regexp has the Unicode modifier if notBefore contains a Unicode property
	*/
	public function testNotBeforeUnicode()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');
		$plugin->add('x', 'x');
		$plugin->notBefore = '\\pL';

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertSame('/x(?!\\pL)/Su', $config['regexp']);
	}

	/**
	* @testdox finalize() sets the tag's template
	*/
	public function testFinalize()
	{
		$this->configurator->Emoticons;
		$this->assertFalse(isset($this->configurator->tags['E']->template));

		$this->configurator->Emoticons->finalize();
		$this->assertTrue(isset($this->configurator->tags['E']->template));
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('Emoticons');

		$this->assertSame(
			$this->configurator->tags['E'],
			$plugin->getTag()
		);
	}

	/**
	* @testdox notIfCondition appears first in the template
	*/
	public function testNotIfCondition()
	{
		$this->configurator->Emoticons->add(':)', '<img/>');
		$this->configurator->Emoticons->notIfCondition = '$foo';

		$this->assertStringStartsWith(
			'<xsl:choose><xsl:when test="$foo"><xsl:value-of select="."/></xsl:when>',
			$this->configurator->Emoticons->getTemplate()
		);
	}
}