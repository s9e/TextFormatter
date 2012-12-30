<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Configurator;
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
	* @testdox $configurator->customFilters is an instance of FilterCollection
	*/
	public function testCustomFiltersInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\FilterCollection',
			$this->configurator->customFilters
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
	* @testdox allowedChildren and allowedDescendants bitfields are added to each tag
	*/
	public function testAsConfigTagBitfields()
	{
		$this->configurator->tags->add('A');
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			array(
				'tags' => array(
					'A' => array(
						'allowedChildren'    => "\1",
						'allowedDescendants' => "\1"
					)
				)
			),
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
	* @testdox $configurator->BBCodes returns $configurator->plugins->load('BBCodes') if the plugin hasn't been loaded already
	*/
	public function testMagicGetLoad()
	{
		$mock = $this->getMock('stdClass', array('exists', 'load'));

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
		$mock = $this->getMock('stdClass', array('exists', 'get'));

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
	* @testdox addHTML5Rules() add root rules
	*/
	public function testAddHTML5RulesRoot()
	{
		$this->configurator->tags->add('UL')->defaultTemplate
			= '<ul><xsl:apply-templates/></ul>';

		$this->configurator->tags->add('LI')->defaultTemplate
			= '<li><xsl:apply-templates/></li>';

		$this->configurator->addHTML5Rules();

		$this->assertSame(array('UL'), $this->configurator->rootRules['allowChild']);
		$this->assertSame(array('LI'), $this->configurator->rootRules['denyChild']);
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

		$this->assertSame(array('LI'), $ul->rules['allowChild']);
		$this->assertSame(array('UL'), $ul->rules['denyChild']);
		$this->assertSame(array('UL'), $li->rules['allowChild']);
		$this->assertSame(array('LI'), $li->rules['denyChild']);
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

		$this->configurator->addHTML5Rules(array('parentHTML' => '<ul>'));

		$this->assertSame(array('LI'), $this->configurator->rootRules['allowChild']);
		$this->assertSame(array('UL'), $this->configurator->rootRules['denyChild']);
	}
}

class DummyPluginConfigurator extends ConfiguratorBase
{
	protected $config = array('foo' => 1);

	public function asConfig()
	{
		return $this->config;
	}

	public function setConfig(array $config)
	{
		$this->config = $config;
	}
}