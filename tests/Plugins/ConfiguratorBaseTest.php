<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\ConfiguratorBase
*/
class ConfiguratorBaseTest extends Test
{
	/**
	* @testdox Constructor overwrites properties with values passed as second argument
	*/
	public function testOverwrite()
	{
		$dummy = new DummyPluginConfigurator($this->configurator, ['bar' => 'bar']);
		$this->assertSame('bar', $dummy->bar);
	}

	/**
	* @testdox Constructor calls setFoo() if 'foo' is being set
	*/
	public function testOverwriteMethod()
	{
		$dummy = new DummyPluginConfigurator($this->configurator, ['foo' => 'bar']);
		$this->assertSame('baz', $dummy->foo);
	}

	/**
	* @testdox Constructor calls setUp()
	*/
	public function testSetUp()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$this->assertSame('foo', $dummy->setup);
	}

	/**
	* @testdox Constructor calls setUp() after overwriting properties
	*/
	public function testSetUpAfterProps()
	{
		$dummy = new DummyPluginConfigurator($this->configurator, ['bar' => 'baz']);
		$this->assertSame('baz', $dummy->setup);
	}

	/**
	* @testdox An exception is thrown if an unknown property is being set by the constructor
	*/
	public function testUnknownProperty()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Unknown property 'baz'");

		new DummyPluginConfigurator($this->configurator, ['baz' => 'baz']);
	}

	/**
	* @testdox Constructor normalizes custom attribute names
	*/
	public function testAttrNameNormalize()
	{
		$dummy = new TagCreatingPluginConfigurator($this->configurator, ['attrName' => 'XXX']);
		$this->assertSame('xxx', $dummy->attrName);
	}

	/**
	* @testdox Constructor normalizes custom tag names
	*/
	public function testTagNameNormalize()
	{
		$dummy = new TagCreatingPluginConfigurator($this->configurator, ['tagName' => 'xxx']);
		$this->assertSame('XXX', $dummy->tagName);
	}

	/**
	* @testdox Constructor throws an exception if we attempt to set an attribute name but the property does not exist
	*/
	public function testAttrNameNotExist()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Unknown property 'attrName'");

		new EmptyPluginConfigurator($this->configurator, ['attrName' => 'XXX']);
	}

	/**
	* @testdox Constructor throws an exception if we attempt to set a tag name but the property does not exist
	*/
	public function testTagNameNotExist()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Unknown property 'tagName'");

		new EmptyPluginConfigurator($this->configurator, ['tagName' => 'XXX']);
	}

	/**
	* @testdox getBaseProperties() returns the values of quickMatch and regexpLimit
	*/
	public function testGetBaseProperties()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$config = $dummy->getBaseProperties();

		$this->assertArrayHasKey('quickMatch', $config);
		$this->assertArrayHasKey('regexpLimit', $config);
	}

	/**
	* @testdox getBaseProperties() returns a className value derived from the configurator's class
	*/
	public function testGetBasePropertiesClass()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$config = $dummy->getBaseProperties();

		$this->assertArrayHasKey('className', $config);
		$this->assertSame(
			's9e\\TextFormatter\\Tests\\Plugins\\DummyPluginParser',
			$config['className']
		);
	}

	/**
	* @testdox getBaseProperties() does not include a "js" element if getJSParser() returns null
	*/
	public function testGetBasePropertiesJSNull()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$config = $dummy->getBaseProperties();

		$this->assertArrayNotHasKey('js', $config);
	}

	/**
	* @testdox getBaseProperties() includes a "js" element if getJSParser() returns a non-null value
	*/
	public function testGetBasePropertiesJS()
	{
		$dummy = new JSPluginConfigurator($this->configurator);
		$config = $dummy->getBaseProperties();

		$this->assertArrayHasKey('js', $config);
		$this->assertEquals(
			new Code('alert(1)'),
			$config['js']
		);
	}

	/**
	* @testdox setQuickMatch() sets the quickMatch property
	*/
	public function testSetQuickMatch()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setQuickMatch('@');
		$this->assertEquals('@', $this->getObjectProperty($dummy, 'quickMatch'));
	}

	/**
	* @testdox setQuickMatch() throws an exception on non-strings
	*/
	public function testSetQuickMatchInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('quickMatch must be a string');

		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setQuickMatch(null);
	}

	/**
	* @testdox disableQuickMatch() sets the quickMatch property to false
	*/
	public function testDisableQuickMatch()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setQuickMatch('@');
		$dummy->disableQuickMatch();
		$this->assertFalse($this->getObjectProperty($dummy, 'quickMatch'));
	}

	/**
	* @testdox setRegexpLimit() sets the regexpLimit property
	*/
	public function testSetRegexpLimit()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setRegexpLimit(1234);
		$this->assertEquals(1234, $this->getObjectProperty($dummy, 'regexpLimit'));
	}

	/**
	* @testdox setRegexpLimit() throws an exception on invalid values
	*/
	public function testSetRegexpLimitInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('regexpLimit must be a number greater than 0');

		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setRegexpLimit(null);
	}

	/**
	* @testdox Offers a default asConfig() implementation that leaves out the configurator instance
	*/
	public function testAsConfig()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);

		$this->assertEquals(
			[
				'foo'   => 'foo',
				'bar'   => 'foo',
				'setup' => 'foo',
				'quickMatch'  => false,
				'regexpLimit' => 50000
			],
			$dummy->asConfig()
		);
	}

	/**
	* @testdox Has a default finalize() method that doesn't do anything
	* @doesNotPerformAssertions
	*/
	public function testFinalize()
	{
		$dummy = new DummyPluginConfigurator(new Configurator);
		$dummy->finalize();
	}

	/**
	* @testdox getJSParser() returns NULL for custom plugins
	*/
	public function testGetJSParserFalse()
	{
		$dummy = new DummyPluginConfigurator(new Configurator);
		$this->assertNull($dummy->getJSParser());
	}

	/**
	* @testdox getJSParser() returns the content of Parser.js for stock plugins
	*/
	public function testGetJSParserStock()
	{
		$configurator = new Configurator;

		$this->assertStringEqualsFile(
			__DIR__ . '/../../src/Plugins/Autolink/Parser.js',
			$configurator->Autolink->getJSParser()
		);
	}

	/**
	* @testdox getJSParser() returns NULL if there's no Parser.js for a stock plugin
	*/
	public function testGetJSParserStockFalse()
	{
		$dummy = new \s9e\TextFormatter\Plugins\TotallyFakeDummyPlugin\Configurator(new Configurator);
		$this->assertNull($dummy->getJSParser());
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$dummy = new TagCreatingPluginConfigurator($this->configurator);

		$this->assertSame($this->configurator->tags['FOO'], $dummy->getTag());
	}

	/**
	* @testdox getTag() throws an exception if the plugin does not have a tagName property set
	*/
	public function testGetTagError()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('No tag associated with this plugin');

		$dummy = new EmptyPluginConfigurator($this->configurator);

		$dummy->getTag();
	}

	/**
	* @testdox getJSHints() returns an empty array by default
	*/
	public function testGetJSHints()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$this->assertSame([], $dummy->getJSHints());
	}
}

class DummyPluginConfigurator extends ConfiguratorBase
{
	public $foo = 'foo';
	public $bar = 'foo';
	public $setup = false;

	public function setFoo()
	{
		$this->foo = 'baz';
	}

	protected function setUp(): void
	{
		parent::setUp();
		$this->setup = $this->bar;
	}
}

class TagCreatingPluginConfigurator extends ConfiguratorBase
{
	public $tagName  = 'FOO';
	public $attrName = 'BAR';

	protected function setUp(): void
	{
		$this->configurator->tags->add($this->tagName);
	}
}

class EmptyPluginConfigurator extends ConfiguratorBase
{
}

class JSPluginConfigurator extends ConfiguratorBase
{
	public function getJSParser()
	{
		return 'alert(1)';
	}
}

namespace s9e\TextFormatter\Plugins\TotallyFakeDummyPlugin;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
}