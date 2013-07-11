<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\TagFilter;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator
*/
class ConfiguratorTest extends Test
{
	public function setUp()
	{
		$this->configurator = new Configurator;
	}

	/**
	* @testdox $configurator->attributeFilters is an instance of AttributeFilterCollection
	*/
	public function testAttributeFiltersInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\AttributeFilterCollection',
			$this->configurator->attributeFilters
		);
	}

	/**
	* @testdox $configurator->javascript is an instance of JavaScript
	*/
	public function testJavaScriptInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript',
			$this->configurator->javascript
		);
	}

	/**
	* @testdox $configurator->plugins is an instance of PluginCollection
	*/
	public function testPluginsInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\PluginCollection',
			$this->configurator->plugins
		);
	}

	/**
	* @testdox $configurator->registeredVars is a publicly accessible array
	*/
	public function testRegisteredVarsVisibility()
	{
		$this->assertInternalType('array', $this->configurator->registeredVars);
	}

	/**
	* @testdox $configurator->rendererGenerator is an instance of RendererGenerators\XSLT
	*/
	public function testRrendererGeneratorInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\RendererGenerators\\XSLT',
			$this->configurator->rendererGenerator
		);
	}

	/**
	* @testdox $configurator->rootRules is an instance of Ruleset
	*/
	public function testRootRulesInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\Ruleset',
			$this->configurator->rootRules
		);
	}

	/**
	* @testdox $configurator->stylesheet is an instance of Stylesheet
	*/
	public function testStylesheetInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Stylesheet',
			$this->configurator->stylesheet
		);
	}

	/**
	* @testdox $configurator->tags is an instance of TagCollection
	*/
	public function testTagsInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\TagCollection',
			$this->configurator->tags
		);
	}

	/**
	* @testdox $configurator->templateChecker is an instance of TemplateChecker
	*/
	public function testTemplateCheckerInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\TemplateChecker',
			$this->configurator->templateChecker
		);
	}

	/**
	* @testdox $configurator->urlConfig is an instance of UrlConfig
	*/
	public function testUrlConfigInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\UrlConfig',
			$this->configurator->urlConfig
		);
	}

	/**
	* @testdox asConfig() returns an array with a rootContext element
	*/
	public function testAsConfigRootContext()
	{
		$config = $this->configurator->asConfig();
		$this->assertInternalType('array', $config);
		$this->assertArrayHasKey('rootContext', $config);
	}

	/**
	* @testdox asConfig() returns an array with a registeredVars element that contains urlConfig
	*/
	public function testAsConfigRegisteredVars()
	{
		$config = $this->configurator->asConfig();
		$this->assertInternalType('array', $config);
		$this->assertArrayHasKey('registeredVars', $config);
		$this->assertArrayHasKey('urlConfig', $config['registeredVars']);
	}

	/**
	* @testdox asConfig() adds regexpLimit to the plugin's configuration if it's not specified and the plugin has a regexp
	*/
	public function testAsConfigAddRegexpLimit()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$plugin->setConfig(['regexp' => '//']);

		$this->configurator->plugins->add('Dummy',$plugin);
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			[
				'plugins' => [
					'Dummy' => [
						'regexpLimit' => 10000
					]
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() removes regexpLimit from the plugin's configuration if it does not have a regexp
	*/
	public function testAsConfigRemoveRegexpLimit()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$plugin->setConfig(['regexpLimit' => 1000]);

		$this->configurator->plugins->add('Dummy',$plugin);
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			[
				'plugins' => [
					'Dummy' => [
						'regexpLimit' => null
					]
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() adds regexpLimitAction to the plugin's configuration if it's not specified and the plugin has a regexp
	*/
	public function testAsConfigAddRegexpLimitAction()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$plugin->setConfig(['regexp' => '//']);

		$this->configurator->plugins->add('Dummy',$plugin);
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			[
				'plugins' => [
					'Dummy' => [
						'regexpLimitAction' => 'warn'
					]
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() removes regexpLimitAction from the plugin's configuration if it does not have a regexp
	*/
	public function testAsConfigRemoveRegexpLimitAction()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$plugin->setConfig(['regexpLimitAction' => 1000]);

		$this->configurator->plugins->add('Dummy',$plugin);
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			[
				'plugins' => [
					'Dummy' => [
						'regexpLimitAction' => null
					]
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() adds quickMatch to the plugin's configuration if available
	*/
	public function testAsConfigAddQuickMatch()
	{
		$this->configurator->plugins->add(
			'Dummy',
			new DummyPluginConfigurator($this->configurator)
		)->setQuickMatch('foo');
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			[
				'plugins' => [
					'Dummy' => [
						'quickMatch' => 'foo'
					]
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() omits a plugin's quickMatch if it's false
	*/
	public function testAsConfigOmitsQuickMatch()
	{
		$this->configurator->plugins->add(
			'Dummy',
			new DummyPluginConfigurator($this->configurator)
		);
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			[
				'plugins' => [
					'Dummy' => [
						'quickMatch' => null
					]
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() adds allowedChildren and allowedDescendants bitfields to each tag
	*/
	public function testAsConfigTagBitfields()
	{
		$this->configurator->tags->add('A');
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			[
				'tags' => [
					'A' => [
						'allowedChildren'    => "\1",
						'allowedDescendants' => "\1"
					]
				]
			],
			$config
		);
	}

	/**
	* @testdox getParser() returns an instance of s9e\TextFormatter\Parser
	*/
	public function testGetParser()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Parser',
			$this->configurator->getParser()
		);
	}


	/**
	* @testdox getParser() removes JavaScript-specific data from tag filters
	*/
	public function testAsConfigRemovesJavaScriptTagFilters()
	{
		$pc = new TagFilter(function($tag){});
		$pc->setJS('function(tag){return false;}');

		$this->configurator->tags->add('A')->filterChain->append($pc);

		$parser = $this->configurator->getParser();
		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');

		$this->assertArrayNotHasKey(
			'js',
			$tagsConfig['A']['filterChain'][0]
		);
	}

	/**
	* @testdox getParser() removes JavaScript-specific data from attribute filters
	*/
	public function testAsConfigRemovesJavaScriptAttributeFilters()
	{
		$filter = new AttributeFilter(function($v){});
		$filter->setJS('function(v){return false;}');

		$this->configurator->tags->add('A')->attributes->add('a')->filterChain->append($filter);

		$parser = $this->configurator->getParser();
		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');

		$this->assertArrayNotHasKey(
			'js',
			$tagsConfig['A']['attributes']['a']['filterChain'][0]
		);
	}

	/**
	* @testdox getRenderer() returns an instance of s9e\TextFormatter\Renderer
	*/
	public function testGetRenderer()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Renderer',
			$this->configurator->getRenderer()
		);
	}

	/**
	* @testdox getRenderer() invokes $this->rendererGenerator->getRenderer()
	*/
	public function testGetRendererInvokesGenerator()
	{
		$mock = $this->getMock('s9e\\TextFormatter\\Configurator\\RendererGenerators\\XSLT');
		$mock->expects($this->once())
		     ->method('getRenderer')
		     ->with($this->configurator->stylesheet);

		$this->configurator->rendererGenerator = $mock;
		$this->configurator->getRenderer();
	}

	/**
	* @testdox getRenderer('PHP') creates a new instance of s9e\TextFormatter\Configurator\RendererGenerators\PHP to generate a renderer
	*/
	public function testGetRendererArg()
	{
		$renderer = $this->configurator->getRenderer('PHP');
		$this->assertRegexp('/^\\w+$/', get_class($renderer));
	}

	/**
	* @testdox getRenderer('PHP', 'foo') creates a new instance of s9e\TextFormatter\Configurator\RendererGenerators\PHP passing 'foo' to its constructor
	*/
	public function testGetRendererArgs()
	{
		$className = uniqid('renderer_');
		$renderer  = $this->configurator->getRenderer('PHP', $className);
		$this->assertSame($className, get_class($renderer));
	}

	/**
	* @testdox $configurator->BBCodes returns $configurator->plugins->load('BBCodes') if the plugin hasn't been loaded already
	*/
	public function testMagicGetLoad()
	{
		$mock = $this->getMock('stdClass', ['exists', 'load']);

		$mock->expects($this->once())
		     ->method('exists')
		     ->with($this->equalTo('BBCodes'))
		     ->will($this->returnValue(false));

		$mock->expects($this->once())
		     ->method('load')
		     ->with($this->equalTo('BBCodes'))
		     ->will($this->returnValue('foobar'));

		$this->configurator->plugins = $mock;

		$this->assertSame('foobar', $this->configurator->BBCodes);
	}

	/**
	* @testdox $configurator->BBCodes returns $configurator->plugins->get('BBCodes') if the plugin has already been loaded
	*/
	public function testMagicGetGet()
	{
		$mock = $this->getMock('stdClass', ['exists', 'get']);

		$mock->expects($this->once())
		     ->method('exists')
		     ->with($this->equalTo('BBCodes'))
		     ->will($this->returnValue(true));

		$mock->expects($this->once())
		     ->method('get')
		     ->with($this->equalTo('BBCodes'))
		     ->will($this->returnValue('foobar'));

		$this->configurator->plugins = $mock;

		$this->assertSame('foobar', $this->configurator->BBCodes);
	}

	/**
	* @testdox $configurator->foo throws an exception
	* @expectedException RuntimeException
	* @expectedExceptionMessage Undefined property
	*/
	public function testMagicGetInvalid()
	{
		$this->configurator->foo;
	}

	/**
	* @testdox isset($configurator->BBCodes) returns $configurator->plugins->exists('BBCodes')
	*/
	public function testMagicIsset()
	{
		$mock = $this->getMock('stdClass', ['exists']);

		$mock->expects($this->once())
		     ->method('exists')
		     ->with($this->equalTo('BBCodes'))
		     ->will($this->returnValue(false));

		$this->configurator->plugins = $mock;

		$this->assertFalse(isset($this->configurator->BBCodes));
	}

	/**
	* @testdox isset($configurator->BBCodes) returns false if the BBCodes plugin is not loaded
	*/
	public function testMagicIssetFalse()
	{
		$this->assertFalse(isset($this->configurator->BBCodes));
	}

	/**
	* @testdox isset($configurator->BBCodes) does not load the BBCodes plugin
	*/
	public function testMagicIssetNotLoad()
	{
		$this->assertFalse(isset($this->configurator->BBCodes));
		$this->assertFalse($this->configurator->plugins->exists('BBCodes'));
	}

	/**
	* @testdox isset($configurator->BBCodes) returns true if the BBCodes plugin is loaded
	*/
	public function testMagicIssetTrue()
	{
		$this->configurator->plugins->load('BBCodes');
		$this->assertTrue(isset($this->configurator->BBCodes));
	}

	/**
	* @testdox isset($configurator->foo) returns false if the "foo" property is not set
	*/
	public function testMagicIssetPropFalse()
	{
		$this->assertFalse(isset($this->configurator->foo));
	}

	/**
	* @testdox isset($configurator->foo) returns true if the "foo" property is set
	*/
	public function testMagicIssetPropTrue()
	{
		$this->configurator->foo = 1;
		$this->assertTrue(isset($this->configurator->foo));
	}

	/**
	* @testdox addHTML5Rules() add root rules
	*/
	public function testAddHTML5RulesRoot()
	{
		$this->configurator->tags->add('UL')->defaultTemplate
			= '<ul><xsl:apply-templates/></ul>';

		$this->configurator->tags->add('LI')->defaultTemplate
			= '<li><xsl:apply-templates/></li>';

		$this->configurator->addHTML5Rules();

		$this->assertSame(['UL'], $this->configurator->rootRules['allowChild']);
		$this->assertSame(['LI'], $this->configurator->rootRules['denyChild']);
	}

	/**
	* @testdox addHTML5Rules() add tag rules
	*/
	public function testAddHTML5RulesTags()
	{
		$ul = $this->configurator->tags->add('UL');
		$ul->defaultTemplate = '<ul><xsl:apply-templates/></ul>';

		$li = $this->configurator->tags->add('LI');
		$li->defaultTemplate = '<li><xsl:apply-templates/></li>';

		$this->configurator->addHTML5Rules();

		$this->assertSame(['LI'], $ul->rules['allowChild']);
		$this->assertSame(['UL'], $ul->rules['denyChild']);
		$this->assertSame(['UL'], $li->rules['allowChild']);
		$this->assertSame(['LI'], $li->rules['denyChild']);
	}

	/**
	* @testdox addHTML5Rules() passes its options to the generator
	*/
	public function testAddHTML5RulesOptions()
	{
		$ul = $this->configurator->tags->add('UL');
		$ul->defaultTemplate = '<ul><xsl:apply-templates/></ul>';

		$li = $this->configurator->tags->add('LI');
		$li->defaultTemplate = '<li><xsl:apply-templates/></li>';

		$this->configurator->addHTML5Rules(['parentHTML' => '<ul>']);

		$this->assertSame(['LI'], $this->configurator->rootRules['allowChild']);
		$this->assertSame(['UL'], $this->configurator->rootRules['denyChild']);
	}

	/**
	* @testdox addHTML5Rules() does not call getRenderer() if a renderer was passed in the options
	*/
	public function testAddHTML5RulesNoGetRenderer()
	{
		$mock = $this->getMock('stdClass', ['getRenderer']);
		$mock->expects($this->never())
		     ->method('getRenderer');

		$renderer = $this->configurator->getRenderer();
		$this->configurator->rendererGenerator = $mock;

		$ul = $this->configurator->tags->add('UL');
		$ul->defaultTemplate = '<ul><xsl:apply-templates/></ul>';

		$li = $this->configurator->tags->add('LI');
		$li->defaultTemplate = '<li><xsl:apply-templates/></li>';

		$this->configurator->addHTML5Rules(['renderer' => $renderer]);
	}

	/**
	* @testdox setRendererGenerator('PHP') sets $configurator->rendererGenerator to an instance of s9e\TextFormatter\Configurator\RendererGenerators\PHP
	*/
	public function testSetRendererGenerator()
	{
		$this->configurator->setRendererGenerator('PHP');
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\RendererGenerators\\PHP',
			$this->configurator->rendererGenerator
		);
	}

	/**
	* @testdox setRendererGenerator() passes extra arguments to the generator's constructor
	*/
	public function testSetRendererGeneratorArguments()
	{
		$this->configurator->setRendererGenerator('PHP', 'Foo');
		$this->assertSame('Foo', $this->configurator->rendererGenerator->className);
	}
}

class DummyPluginConfigurator extends ConfiguratorBase
{
	protected $config = ['foo' => 1];

	public function asConfig()
	{
		return $this->config;
	}

	public function setConfig(array $config)
	{
		$this->config = $config;
	}
}