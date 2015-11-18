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
		$this->configurator->plugins->load('Censor', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Censor', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['CENSOR']->attributes->exists('bar'));
	}

	/**
	* @testdox asConfig() returns NULL if no words were added
	*/
	public function testNullConfig()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$this->assertNull($plugin->asConfig());
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
		$plugin = $this->configurator->plugins->load('Censor', ['tagName' => 'FOO']);
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
		$plugin = $this->configurator->plugins->load('Censor', ['attrName' => 'bar']);
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
			[
				['/^apple$/Diu', 'banana'],
				['/^lemon$/Diu', 'citrus']
			],
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
			[['/^(?>apple|cherry)$/Diu', 'banana']],
			$config['replacements']
		);
	}

	/**
	* @testdox Words using the default replacement do not appear in the replacements
	*/
	public function testAsConfigDefaultReplacement()
	{
		$plugin = $this->configurator->plugins->load('Censor', ['defaultReplacement' => '**']);
		$plugin->add('apple', '**');
		$plugin->add('cherry', 'banana');

		$config = $plugin->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertSame(
			[['/^cherry$/Diu', 'banana']],
			$config['replacements']
		);
	}

	/**
	* @testdox asConfig() creates a JavaScript variant for the plugin's regexp
	*/
	public function testAsConfigJavaScriptRegexp()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple');

		$config = $plugin->asConfig();

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$config['regexp']
		);
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Code',
			$config['regexp']->get('JS')
		);
	}

	/**
	* @testdox asConfig() creates a JavaScript variant for the regexps used in replacements
	*/
	public function testAsConfigJavaScriptReplacements()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple', 'banana');

		$config = $plugin->asConfig();

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$config['replacements'][0][0]
		);
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Code',
			$config['replacements'][0][0]->get('JS')
		);
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('Censor');

		$this->assertSame(
			$this->configurator->tags['CENSOR'],
			$plugin->getTag()
		);
	}

	/**
	* @testdox getHelper() returns a configured instance of Helper
	*/
	public function testGetHelper()
	{
		$this->configurator->Censor->add('foo');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\Censor\\Helper',
			$this->configurator->Censor->getHelper()
		);
	}

	/**
	* @testdox getHelper() returns an instance of Helper even if no words were added
	*/
	public function testGetHelperNoWords()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\Censor\\Helper',
			$this->configurator->Censor->getHelper()
		);
	}

	/**
	* @testdox add() does not throw an exception on duplicates
	*/
	public function testAddDuplicate()
	{
		$this->configurator->Censor->add('foo');
		$this->configurator->Censor->add('foo');
	}

	/**
	* @testdox asConfig() does not return an entry for "allowed" by default
	*/
	public function testAsConfigAllowedDefault()
	{
		$this->configurator->Censor->add('foo');
		$this->assertArrayNotHasKey('allowed', $this->configurator->Censor->asConfig());
	}

	/**
	* @testdox asConfig() returns an entry for "allowed" if any word was allowed with allow()
	*/
	public function testAsConfigAllowed()
	{
		$this->configurator->Censor->add('foo*');
		$this->configurator->Censor->allow('fool');
		$this->assertArrayHasKey('allowed', $this->configurator->Censor->asConfig());
	}

	/**
	* @testdox asConfig() returns NULL if all censored words are also on the allowed list
	*/
	public function testAsConfigAllowedAll()
	{
		$this->configurator->Censor->add('foo');
		$this->configurator->Censor->allow('foo');
		$this->assertNull($this->configurator->Censor->asConfig());
	}

	/**
	* @testdox getJSHints() returns ['CENSOR_HAS_REPLACEMENTS' => 0] by default
	*/
	public function testGetJSHintsFalse()
	{
		$plugin = $this->configurator->Censor;
		$plugin->add('foo');
		$this->assertSame(
			['CENSOR_HAS_REPLACEMENTS' => 0],
			$plugin->getJSHints()
		);
	}

	/**
	* @testdox getJSHints() returns ['CENSOR_HAS_REPLACEMENTS' => 0] if any replacement is set
	*/
	public function testGetJSHintsTrue()
	{
		$plugin = $this->configurator->Censor;
		$plugin->add('foo', 'bar');
		$this->assertSame(
			['CENSOR_HAS_REPLACEMENTS' => 1],
			$plugin->getJSHints()
		);
	}
}