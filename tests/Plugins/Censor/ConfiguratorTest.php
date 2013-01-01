<?php

namespace s9e\TextFormatter\Tests\Plugins\Censor;

use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Plugins\Censor\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Censor\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates a "CENSOR" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Censor');
		$this->assertTrue($this->configurator->tags->exists('CENSOR'));
	}

	/**
	* @testdox Automatically creates a "with" attribute with required=false
	*/
	public function testCreatesAttribute()
	{
		$this->configurator->plugins->load('Censor');
		$this->assertTrue(isset($this->configurator->tags['CENSOR']->attributes['with']));
		$this->assertFalse($this->configurator->tags['CENSOR']->attributes['with']->required);
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('CENSOR');
		$this->configurator->plugins->load('Censor');

		$this->assertSame($tag, $this->configurator->tags->get('CENSOR'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Censor', array('tagName' => 'FOO'));
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Censor', array('attrName' => 'bar'));
		$this->assertTrue($this->configurator->tags['CENSOR']->attributes->exists('bar'));
	}

	/**
	* @testdox asConfig() returns FALSE if no words were added
	*/
	public function testFalseConfig()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$this->assertFalse($plugin->asConfig());
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testAsConfig()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple');

		$this->assertArrayHasKey('regexp', $plugin->asConfig());
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$plugin = $this->configurator->plugins->load('Censor', array('tagName' => 'FOO'));
		$plugin->add('apple');

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('FOO', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttrName()
	{
		$plugin = $this->configurator->plugins->load('Censor', array('attrName' => 'bar'));
		$plugin->add('apple');

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('bar', $config['attrName']);
	}

	/**
	* @testdox Returns the replacements in its config in the form [[regexp, replacement]]
	*/
	public function testAsConfigReplacements()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple', 'banana');
		$plugin->add('lemon', 'citrus');

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertSame(
			array(
				array('/^apple$/Diu', 'banana'),
				array('/^lemon$/Diu', 'citrus')
			),
			$config['replacements']
		);
	}

	/**
	* @testdox Words sharing the same replacement share a common regexp
	*/
	public function testAsConfigReplacementsMerge()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple', 'banana');
		$plugin->add('cherry', 'banana');

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertSame(
			array(array('/^(?:apple|cherry)$/Diu', 'banana')),
			$config['replacements']
		);
	}

	/**
	* @testdox Words using the default replacement do not appear in the replacements
	*/
	public function testAsConfigDefaultReplacement()
	{
		$plugin = $this->configurator->plugins->load('Censor', array('defaultReplacement' => '**'));
		$plugin->add('apple', '**');
		$plugin->add('cherry', 'banana');

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertSame(
			array(array('/^cherry$/Diu', 'banana')),
			$config['replacements']
		);
	}

	/**
	* @testdox asConfig() creates a Javascript variant for the plugin's regexp
	*/
	public function testAsConfigJavascriptRegexp()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple');

		$config = $plugin->asConfig();

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$config['regexp']
		);
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Javascript\\RegExp',
			$config['regexp']->get('Javascript')
		);
	}

	/**
	* @testdox asConfig() creates a Javascript variant for the regexps used in replacements
	*/
	public function testAsConfigJavascriptReplacements()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple', 'banana');

		$config = $plugin->asConfig();

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$config['replacements'][0][0]
		);
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Javascript\\RegExp',
			$config['replacements'][0][0]->get('Javascript')
		);
	}
}