<?php

namespace s9e\TextFormatter\Tests\Configurator;

use stdClass;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript
*/
class JavaScriptTest extends Test
{
	/**
	* @testdox getMinifier() returns an instance of Noop by default
	*/
	public function testGetMinifier()
	{
		$javascript = new JavaScript(new Configurator);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\Noop',
			$javascript->getMinifier()
		);
	}

	/**
	* @testdox setMinifier() accepts the name of a minifier type
	*/
	public function testSetMinifierName()
	{
		$javascript = new JavaScript(new Configurator);
		$javascript->setMinifier('ClosureCompilerService');

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\ClosureCompilerService',
			$javascript->getMinifier()
		);
	}

	/**
	* @testdox setMinifier() accepts an object that implements Minifier
	*/
	public function testSetMinifierInstance()
	{
		$javascript = new JavaScript(new Configurator);
		$minifier   = new ClosureCompilerService;

		$javascript->setMinifier($minifier);

		$this->assertSame($minifier, $javascript->getMinifier());
	}

	/**
	* @testdox getParser() calls the minifier and returns its result
	*/
	public function testMinifierReturn()
	{
		$mock = $this->getMock('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\Noop');
		$mock->expects($this->once())
		     ->method('minify')
		     ->will($this->returnValue('/**/'));

		$this->configurator->javascript->setMinifier($mock);

		$this->assertSame('/**/', $this->configurator->javascript->getParser());
	}

	/**
	* @testdox A plugin's quickMatch value is preserved if it's valid UTF-8
	*/
	public function testQuickMatchUTF8()
	{
		$this->configurator->plugins->load('Escaper', ['quickMatch' => 'foo']);

		$this->assertContains(
			'quickMatch:"foo"',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox A plugin's quickMatch value is discarded if it contains no valid UTF-8
	*/
	public function testQuickMatchUTF8Bad()
	{
		$this->configurator->plugins->load('Escaper', ['quickMatch' => "\xC0"]);

		$this->assertNotContains(
			'quickMatch:',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox If a plugin's quickMatch value contains bad UTF-8, only the first consecutive valid characters are kept
	*/
	public function testQuickMatchUTF8Partial()
	{
		$this->configurator->plugins->load('Escaper', ['quickMatch' => "\xC0xÿz"]);

		$this->assertContains(
			'quickMatch:"x\\u00ffz"',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox A plugin with no JS parser does not appear in the source
	*/
	public function testPluginNoParser()
	{
		$this->configurator->plugins['Foobar'] = new NoJSPluginConfigurator($this->configurator);

		$this->assertNotContains(
			'Foobar',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox getParser() throws an exception if it encounters a value that cannot be encoded into JavaScript
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot encode non-scalar value
	*/
	public function testNonScalaConfigException()
	{
		$this->configurator->registeredVars['foo'] = new NonScalarConfigThing;
		$this->configurator->javascript->getParser();
	}

	/**
	* @testdox Attribute generators are converted
	*/
	public function testAttributeGenerator()
	{
		$js = 'function() { return "foo"; }';

		$callback = new ProgrammableCallback(function() { return 'foo'; });
		$callback->setJS($js);

		$this->configurator->tags->add('FOO')->attributes->add('bar')->generator = $callback;

		$this->assertContains(
			$js,
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox Built-in attribute filters are converted
	*/
	public function testAttributeFilterBuiltIn()
	{
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			$this->configurator->attributeFilters->get('#number')
		);

		$this->assertContains(
			'filterChain:[function(attrValue,attrName){return BuiltInFilters.filterNumber(attrValue);}]',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox An attribute filter that uses a built-in filter as callback is converted
	*/
	public function testAttributeFilterBuiltInCallback()
	{
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			's9e\\TextFormatter\\Parser\\BuiltInFilters::filterInt'
		);

		$this->assertContains(
			'filterChain:[function(attrValue,attrName){return BuiltInFilters.filterInt(attrValue);}]',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox An attribute filter with no JS representation unconditionally returns false
	*/
	public function testAttributeFilterMissing()
	{
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			function() {}
		);

		$this->assertContains(
			'cCB952229=function(){return false;}',
			$this->configurator->javascript->getParser()
		);
	}
}

class NonScalarConfigThing implements ConfigProvider
{
	public function asConfig()
	{
		return ['foo' => new stdClass];
	}
}

class NoJSPluginConfigurator extends ConfiguratorBase
{
	public function asConfig()
	{
		return ['regexp' => '//'];
	}
}