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
	* @testdox $configurator->bundleGenerator is an instance of BundleGenerator
	*/
	public function testBundleGeneratorInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\BundleGenerator',
			$this->configurator->bundleGenerator
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
	public function testRendererGeneratorInstance()
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
	* @testdox $configurator->rulesGenerator is an instance of RulesGenerator
	*/
	public function testRulesGeneratorInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\RulesGenerator',
			$this->configurator->rulesGenerator
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
	* @testdox $configurator->templateNormalizer is an instance of TemplateNormalizer
	*/
	public function testTemplateNormalizerInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\TemplateNormalizer',
			$this->configurator->templateNormalizer
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
	* @testdox asConfig() calls every plugin's finalize() before retrieving their config
	*/
	public function testAsConfigFinalize()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$this->configurator->plugins->add('Dummy', $plugin);

		$config = $this->configurator->asConfig();
		$this->assertArrayMatches(
			['plugins' => ['Dummy' => ['finalized' => true]]],
			$config
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

		$this->configurator->plugins->add('Dummy', $plugin);
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

		$this->configurator->plugins->add('Dummy', $plugin);
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

		$this->configurator->plugins->add('Dummy', $plugin);
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

		$this->configurator->plugins->add('Dummy', $plugin);
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
	* @testdox getRenderer('PHP', '/tmp') creates a new instance of s9e\TextFormatter\Configurator\RendererGenerators\PHP passing '/tmp' to its constructor
	*/
	public function testGetRendererArgs()
	{
		$cacheDir = sys_get_temp_dir();
		$renderer = $this->configurator->getRenderer('PHP', $cacheDir);

		$this->assertFileExists(
			$cacheDir . '/Renderer_b508f06eee8492e13c62ffc5c3c69011a6a769ff.php'
		);
		unlink($cacheDir . '/Renderer_b508f06eee8492e13c62ffc5c3c69011a6a769ff.php');
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
	* @testdox addHTML5Rules() adds root rules
	*/
	public function testAddHTML5RulesRoot()
	{
		$this->configurator->tags->add('UL')->defaultTemplate
			= '<ul><xsl:apply-templates/></ul>';

		$this->configurator->tags->add('LI')->defaultTemplate
			= '<li><xsl:apply-templates/></li>';

		$this->configurator->addHTML5Rules();

		$this->assertSame(['LI'], $this->configurator->rootRules['denyChild']);
	}

	/**
	* @testdox addHTML5Rules() does not overwrite boolean rules
	*/
	public function testAddHTML5RulesRootNoOverwrite()
	{
		$this->configurator->rootRules->noBrDescendant(false);
		$this->configurator->addHTML5Rules(['parentHTML' => '<pre>']);

		$this->assertFalse($this->configurator->rootRules['noBrDescendant']);
	}

	/**
	* @testdox addHTML5Rules() adds tag rules
	*/
	public function testAddHTML5RulesTags()
	{
		$ul = $this->configurator->tags->add('UL');
		$ul->defaultTemplate = '<ul><xsl:apply-templates/></ul>';

		$li = $this->configurator->tags->add('LI');
		$li->defaultTemplate = '<li><xsl:apply-templates/></li>';

		$this->configurator->addHTML5Rules();

		$this->assertSame(['UL'], $ul->rules['denyChild']);
		$this->assertSame(['LI'], $li->rules['denyChild']);
	}

	/**
	* @testdox addHTML5Rules() does not overwrite boolean tag rules
	*/
	public function testAddHTML5RulesTagsNoOverwrite()
	{
		$ul = $this->configurator->tags->add('PRE');
		$ul->defaultTemplate = '<pre><xsl:apply-templates/></pre>';
		$ul->rules->noBrDescendant(false);

		$this->configurator->addHTML5Rules();

		$this->assertFalse($ul->rules['noBrDescendant']);
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

		$this->assertSame(['UL'], $this->configurator->rootRules['denyChild']);
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
		$this->configurator->setRendererGenerator('PHP', '/tmp');
		$this->assertSame('/tmp', $this->configurator->rendererGenerator->cacheDir);
	}

	/**
	* @testdox setRendererGenerator() returns the new instance of RendererGenerator
	*/
	public function testSetRendererGeneratorReturn()
	{
		$return = $this->configurator->setRendererGenerator('PHP');

		$this->assertSame(
			$this->configurator->rendererGenerator,
			$return
		);
	}

	/**
	* @testdox loadBundle('../Invalid') throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid bundle name
	*/
	public function testLoadBundleInvalid()
	{
		$this->configurator->loadBundle('../Invalid');
	}

	/**
	* @testdox loadBundle('Forum') adds the Forum bundle's configuration
	*/
	public function testLoadBundle()
	{
		$this->configurator->loadBundle('Forum');
		$this->assertTrue($this->configurator->BBCodes->exists('B'));
	}

	/**
	* @testdox saveBundle('Foo', '/tmp/foo') saves a bundle Foo to /tmp/foo
	*/
	public function testSaveBundle()
	{
		$filepath = $this->tempnam();
		$this->configurator->saveBundle('Foo', $filepath);

		$this->assertFileExists($filepath);

		$file = file_get_contents($filepath);
		$this->assertStringStartsWith('<?php', $file);
		$this->assertContains('class Foo', $file);
	}

	/**
	* @testdox saveBundle() returns the number of bytes written to disk
	*/
	public function testSaveBundleReturn()
	{
		$filepath = $this->tempnam();
		$return   = $this->configurator->saveBundle('Foo', $filepath);

		$this->assertSame(filesize($filepath), $return);
	}

	/**
	* @testdox saveBundle() passes its third parameter to BundleGenerator::generate()
	*/
	public function testSaveBundleForwardParameters()
	{
		$mock = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\BundleGenerator')
		             ->disableOriginalConstructor()
		             ->getMock();

		$mock->expects($this->once())
		     ->method('generate')
		     ->with('Foo', ['finalizeParser' => 'finalize']);

		$this->configurator->bundleGenerator = $mock;
		$this->configurator->saveBundle('Foo', $this->tempnam(), ['finalizeParser' => 'finalize']);
	}

	/**
	* @testdox finalize() returns a parser and a renderer
	*/
	public function testFinalizeDefault()
	{
		$return = $this->configurator->finalize();

		$this->assertArrayHasKey('parser', $return);
		$this->assertInstanceOf('s9e\\TextFormatter\\Parser', $return['parser']);

		$this->assertArrayHasKey('renderer', $return);
		$this->assertInstanceOf('s9e\\TextFormatter\\Renderer', $return['renderer']);
	}

	/**
	* @testdox finalize(['returnParser' => false]) does not return a parser
	*/
	public function testFinalizeNoParser()
	{
		$return = $this->configurator->finalize(['returnParser' => false]);

		$this->assertArrayNotHasKey('parser', $return);
	}

	/**
	* @testdox finalize(['returnRenderer' => false]) does not return a renderer
	*/
	public function testFinalizeNoRenderer()
	{
		$return = $this->configurator->finalize(['returnRenderer' => false]);

		$this->assertArrayNotHasKey('renderer', $return);
	}

	/**
	* @testdox finalize() calls addHTML5Rules() by default
	*/
	public function testFinalizeAddHTML5Rules()
	{
		$configurator = $this->getMock('s9e\\TextFormatter\\Configurator', ['addHTML5Rules']);
		$configurator->expects($this->once())
		             ->method('addHTML5Rules');

		$configurator->finalize();
	}

	/**
	* @testdox finalize() passes its options to addHTML5Rules()
	*/
	public function testFinalizeAddHTML5RulesOptions()
	{
		$configurator = $this->getMock('s9e\\TextFormatter\\Configurator', ['addHTML5Rules']);
		$configurator->expects($this->once())
		             ->method('addHTML5Rules')
		             ->with($this->arrayHasKey('foo'));

		$configurator->finalize(['foo' => 'bar']);
	}

	/**
	* @testdox finalize() passes a renderer to addHTML5Rules()
	*/
	public function testFinalizeAddHTML5RulesRenderer()
	{
		$configurator = $this->getMock('s9e\\TextFormatter\\Configurator', ['addHTML5Rules']);
		$configurator->expects($this->once())
		             ->method('addHTML5Rules')
		             ->with($this->arrayHasKey('renderer'));

		$configurator->finalize();
	}

	/**
	* @testdox finalize(['addHTML5Rules' => false]) does not call addHTML5Rules() by default
	*/
	public function testFinalizeNoAddHTML5Rules()
	{
		$configurator = $this->getMock('s9e\\TextFormatter\\Configurator', ['addHTML5Rules']);
		$configurator->expects($this->never())
		             ->method('addHTML5Rules');

		$configurator->finalize(['addHTML5Rules' => false]);
	}

	/**
	* @testdox finalize(['finalizeParser' => $callback]) calls $callback and passes it an instance of Parser
	*/
	public function testFinalizeParserCallback()
	{
		$mock = $this->getMock('stdClass', ['foo']);
		$mock->expects($this->once())
		     ->method('foo')
		     ->with($this->isInstanceOf('s9e\\TextFormatter\\Parser'));

		$this->configurator->finalize(['finalizeParser' => [$mock, 'foo']]);
	}

	/**
	* @testdox finalize(['finalizeRenderer' => $callback]) calls $callback and passes it an instance of Renderer
	*/
	public function testFinalizeRendererCallback()
	{
		$mock = $this->getMock('stdClass', ['foo']);
		$mock->expects($this->once())
		     ->method('foo')
		     ->with($this->isInstanceOf('s9e\\TextFormatter\\Renderer'));

		$this->configurator->finalize(['finalizeRenderer' => [$mock, 'foo']]);
	}

	/**
	* @testdox finalize(['optimizeConfig' => true]) reduces the size of the serialized parser at the cost of not being configurable at runtime
	*/
	public function testFinalizeOptimizeConfig()
	{
		$this->configurator->tags->add('X');
		$this->configurator->tags->add('Y');

		$return1 = $this->configurator->finalize();
		$return2 = $this->configurator->finalize(['optimizeConfig' => true]);

		$this->assertLessThan(
			strlen(serialize($return1['parser'])),
			strlen(serialize($return2['parser']))
		);
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

	public function finalize()
	{
		$this->config['finalized'] = true;
	}
}