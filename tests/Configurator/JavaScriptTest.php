<?php

namespace s9e\TextFormatter\Tests\Configurator;

use stdClass;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension json
* @covers s9e\TextFormatter\Configurator\JavaScript
*/
class JavaScriptTest extends Test
{
	protected function setUp(): void
	{
		parent::setUp();
		$this->configurator->enableJavaScript();
	}

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
	* @testdox setMinifier() accepts the name of a minifier type and returns an instance
	*/
	public function testSetMinifierName()
	{
		$javascript = new JavaScript(new Configurator);

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\ClosureCompilerService',
			$javascript->setMinifier('ClosureCompilerService')
		);
	}

	/**
	* @testdox setMinifier() accepts the name of a minifier type plus any number of arguments passed to the minifier's constructor
	*/
	public function testSetMinifierArguments()
	{
		$javascript = new JavaScript(new Configurator);
		$command    = 'npx google-closure-compiler-linux';

		$this->assertSame(
			$command,
			$javascript->setMinifier('ClosureCompilerApplication', $command)->command
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
	* @testdox setMinifier() returns the new instance
	*/
	public function testSetMinifierReturn()
	{
		$javascript  = new JavaScript(new Configurator);
		$oldMinifier = $javascript->getMinifier();

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifiers\\Noop',
			$javascript->setMinifier('Noop')
		);

		$this->assertNotSame($oldMinifier, $javascript->getMinifier());
	}

	/**
	* @testdox getParser() calls the minifier and returns its result
	*/
	public function testMinifierReturn()
	{
		$mock = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		             ->onlyMethods(['minify'])
		             ->getMock();
		$mock->expects($this->once())
		     ->method('minify')
		     ->will($this->returnValue('/**/'));

		$this->configurator->enableJavaScript();
		$this->configurator->javascript->setMinifier($mock);

		$this->assertStringContainsString('/**/', $this->configurator->javascript->getParser());
	}

	/**
	* @testdox getParser() optionally accepts a config array as argument
	*/
	public function testGetParserWithConfig()
	{
		$configurator = new Configurator;
		$configurator->tags->add('FOO');
		$configurator->rootRules->allowChild('FOO');

		$this->configurator->enableJavaScript();

		$this->assertStringNotContainsString(
			'FOO',
			$this->configurator->javascript->getParser()
		);

		$this->assertStringContainsString(
			'FOO',
			$this->configurator->javascript->getParser($configurator->asConfig())
		);
	}

	/**
	* @testdox A plugin's quickMatch value is preserved if it's valid UTF-8
	*/
	public function testQuickMatchUTF8()
	{
		$this->configurator->plugins->load('Escaper', ['quickMatch' => 'foo']);

		$this->assertStringContainsString(
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

		$this->assertStringNotContainsString(
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

		$this->assertStringContainsString(
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

		$this->assertStringNotContainsString(
			'Foobar',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox getParser() throws an exception if it encounters a value that cannot be encoded into JavaScript
	*/
	public function testNonScalarConfigException()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Cannot encode instance of stdClass');

		$this->configurator->registeredVars['foo'] = new NonScalarConfigThing;
		$this->configurator->javascript->getParser();
	}

	/**
	* @testdox Built-in attribute filters are converted
	*/
	public function testAttributeFilterBuiltIn()
	{
		$this->configurator->rootRules->allowChild('FOO');
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			$this->configurator->attributeFilters->get('#int')
		);

		$js = $this->configurator->javascript->getParser();

		$this->assertStringContainsString(
			'function(attrValue,attrName){return NumericFilter.filterInt(attrValue);}',
			$js
		);
	}

	/**
	* @testdox An attribute filter that uses a built-in filter as callback is converted
	*/
	public function testAttributeFilterBuiltInCallback()
	{
		$this->configurator->rootRules->allowChild('FOO');
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			's9e\\TextFormatter\\Parser\\AttributeFilters\\NumericFilter::filterInt'
		);

		$js = $this->configurator->javascript->getParser();

		$this->assertStringContainsString(
			'function(tag,tagConfig){return filterAttributes(tag,tagConfig,registeredVars,logger);}',
			$js
		);
	}

	/**
	* @testdox An attribute filter with no JS representation unconditionally returns false
	*/
	public function testAttributeFilterMissing()
	{
		$this->configurator->rootRules->allowChild('FOO');
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain->append(
			function() {}
		);

		$js = $this->configurator->javascript->getParser();

		$this->assertStringContainsString('filterChain:[returnFalse]', $js);
	}

	/**
	* @testdox The name of a registered vars is expressed in quotes
	*/
	public function testRegisteredVarBracket()
	{
		$this->configurator->registeredVars = ['foo' => 'bar'];

		$this->assertStringContainsString(
			'registeredVars={"foo":"bar"}',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox "cacheDir" is removed from registered vars
	*/
	public function testRegisteredVarCacheDir()
	{
		$this->configurator->registeredVars = ['cacheDir' => '', 'foo' => 'bar'];

		$src = $this->configurator->javascript->getParser();

		$this->assertStringContainsString('registeredVars={"foo":"bar"}', $src);
		$this->assertStringNotContainsString('cacheDir', $src);
	}

	/**
	* @testdox "className" is removed from the plugins' config
	*/
	public function testOmitClassName()
	{
		$this->configurator->Autolink;

		$src = $this->configurator->javascript->getParser();

		$this->assertStringNotContainsString('className', $src);
	}

	/**
	* @testdox Callbacks use the bracket syntax to access registered vars
	*/
	public function testCallbackRegisteredVarBracket()
	{
		$this->configurator->registeredVars = ['foo' => 'bar'];

		$this->configurator->rootRules->allowChild('FOO');
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain
			->append('strtolower')
			->resetParameters()
			->addParameterByName('foo');

		$this->assertStringContainsString(
			'registeredVars["foo"]',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox The normal Logger is present by default
	*/
	public function testLoggerDefault()
	{
		$js = $this->configurator->javascript->getParser();

		$this->assertStringContainsString(
			file_get_contents(__DIR__ . '/../../src/Parser/Logger.js'),
			$js
		);

		$this->assertStringNotContainsString(
			file_get_contents(__DIR__ . '/../../src/Parser/NullLogger.js'),
			$js
		);
	}

	/**
	* @testdox The null Logger is use if getLogger() is not exported
	*/
	public function testNullLogger()
	{
		$this->configurator->javascript->exports = ['preview'];
		$js = $this->configurator->javascript->getParser();

		$this->assertStringNotContainsString(
			file_get_contents(__DIR__ . '/../../src/Parser/Logger.js'),
			$js
		);

		$this->assertStringContainsString(
			file_get_contents(__DIR__ . '/../../src/Parser/NullLogger.js'),
			$js
		);
	}

	/**
	* @testdox Callbacks correctly encode values
	*/
	public function testCallbackValue()
	{
		$this->configurator->rootRules->allowChild('FOO');
		$this->configurator->tags->add('FOO')->attributes->add('bar')->filterChain
			->append('strtolower')
			->resetParameters()
			->addParameterByValue('foo')
			->addParameterByValue(42);

		$this->assertStringContainsString(
			'("foo",42)',
			$this->configurator->javascript->getParser()
		);
	}

	/**
	* @testdox Tag config is wholly deduplicated
	*/
	public function testOptimizeWholeTag()
	{
		$this->configurator->rootRules->allowChild('X');
		$this->configurator->rootRules->allowChild('Y');
		$this->configurator->tags->add('X');
		$this->configurator->tags->add('Y');
		$this->configurator->javascript->exports = ['preview'];
		$js = $this->configurator->javascript->getParser();

		$this->assertStringNotContainsString('"X":{', $js);
		$this->assertStringNotContainsString('"Y":{', $js);
		$this->assertMatchesRegularExpression('(tagsConfig=\\{"X":(\\w+),"Y":\\1)', $js);
	}

	/**
	* @testdox The public API is created if anything is exported
	*/
	public function testExport()
	{
		$this->configurator->javascript->exports = ['preview'];
		$this->assertStringContainsString("window['s9e']", $this->configurator->javascript->getParser());
	}

	/**
	* @testdox The public API is not created if nothing is exported
	*/
	public function testNoExport()
	{
		$this->configurator->javascript->exports = [];
		$this->assertStringNotContainsString("window['s9e']['TextFormatter']", $this->configurator->javascript->getParser());
	}

	/**
	* @testdox Exports' order is consistent
	*/
	public function testExportOrder()
	{
		$this->configurator->javascript->exports = ['parse', 'preview'];
		$js1 = $this->configurator->javascript->getParser();

		$this->configurator->javascript->exports = ['preview', 'parse'];
		$js2 = $this->configurator->javascript->getParser();

		$this->assertSame($js1, $js2);
	}

	/**
	* @testdox Preserves live preview attributes
	*/
	public function testLivePreviewAttributes()
	{
		$this->configurator->tags->add('X')->template = '<hr data-s9e-livepreview-ignore-attrs="style"/>';

		$this->assertStringContainsString('data-s9e-livepreview-ignore-attrs', $this->configurator->javascript->getParser());
	}

	/**
	* @testdox Prefills the function cache
	*/
	public function testFunctionCache()
	{
		$this->configurator->tags->add('X')->template = '<hr data-s9e-livepreview-onupdate="alert(1)" data-s9e-livepreview-onrender="if(1){{alert(1);}}"/>';

		$js = $this->configurator->javascript->getParser();
		$this->assertStringContainsString('functionCache={"167969434":/**@this {!Element}*/function(){alert(1);},"721683742":/**@this {!Element}*/function(){if(1){alert(1);}}}', $js);
	}

	/**
	* @testdox Does not prefill the function cache with dynamic code
	*/
	public function testFunctionCacheDynamic()
	{
		$this->configurator->tags->add('X')->template = '<hr data-s9e-livepreview-onupdate="alert({@x})"/>';

		$js = $this->configurator->javascript->getParser();
		$this->assertStringContainsString('functionCache={}', $js);
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