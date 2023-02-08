<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser
*/
class PluginsHandlingTest extends Test
{
	/**
	* @testdox disablePlugin() disables given plugin
	*/
	public function testDisablePlugin()
	{
		$dummy = new PluginsHandlingDummy;
		$dummy->disablePlugin('Test');

		$this->assertTrue($dummy->pluginsConfig['Test']['isDisabled']);
	}

	/**
	* @testdox disablePlugin() does not have side-effects due to references
	*/
	public function testDisablePluginReference()
	{
		$pluginsConfig = ['P1' => []];
		$pluginsConfig['P2'] =& $pluginsConfig['P1'];

		$dummy = new PluginsHandlingDummy;
		$dummy->pluginsConfig = $pluginsConfig;

		$dummy->disablePlugin('P1');

		$this->assertEquals(
			[
				'P1' => ['isDisabled' => true],
				'P2' => []
			],
			$dummy->pluginsConfig
		);
	}

	/**
	* @testdox enablePlugin() re-enables a disabled plugin
	*/
	public function testEnablePlugin()
	{
		$dummy = new PluginsHandlingDummy;
		$dummy->disablePlugin('Test');
		$dummy->enablePlugin('Test');

		$this->assertTrue(empty($dummy->pluginsConfig['Test']['isDisabled']));
	}

	/**
	* @testdox Nothing happens if disablePlugin() is called for an inexistent plugin
	*/
	public function testDisableInexistentPlugin()
	{
		$dummy = new PluginsHandlingDummy;
		$dummy->disablePlugin('Unknown');

		$this->assertArrayNotHasKey('Unknown', $dummy->pluginsConfig);
	}

	/**
	* @testdox Nothing happens if enablePlugin() is called for an inexistent plugin
	*/
	public function testEnableInexistentPlugin()
	{
		$dummy = new PluginsHandlingDummy;
		$dummy->enablePlugin('Unknown');

		$this->assertArrayNotHasKey('Unknown', $dummy->pluginsConfig);
	}

	protected function getMockPlugin(Parser $parser)
	{
		return $this->getMockBuilder('s9e\\TextFormatter\\Plugins\\ParserBase')
		            ->setMethods(['parse'])
		            ->setConstructorArgs([$parser, []])
		            ->getMock();
	}

	/**
	* @testdox executePluginParsers() executes plugins' parse() method
	*/
	public function testExecutePluginParsers()
	{
		$dummy  = new PluginsHandlingDummy;
		$plugin = $this->getMockPlugin($dummy);
		$plugin->expects($this->once())
		       ->method('parse');

		$dummy->pluginParsers['Test'] = [$plugin, 'parse'];

		$dummy->executePluginParsers();
	}

	/**
	* @testdox executePluginParsers() does not execute disabled plugins
	*/
	public function testExecutePluginParsersDisabledPlugin()
	{
		$dummy  = new PluginsHandlingDummy;
		$plugin = $this->getMockPlugin($dummy);
		$plugin->expects($this->never())
		       ->method('parse');

		$dummy->pluginParsers['Test'] = [$plugin, 'parse'];
		$dummy->disablePlugin('Test');

		$dummy->executePluginParsers();
	}

	/**
	* @testdox executePluginParsers() executes a plugin if its quickMatch test passes
	*/
	public function testExecutePluginParsersQuickMatchPass()
	{
		$dummy  = new PluginsHandlingDummy('[.....');
		$plugin = $this->getMockPlugin($dummy);
		$plugin->expects($this->once())
		       ->method('parse');

		$dummy->pluginParsers['Test'] = [$plugin, 'parse'];
		$dummy->pluginsConfig['Test']['quickMatch'] = '[';

		$dummy->executePluginParsers();
	}

	/**
	* @testdox executePluginParsers() does not execute a plugin if its quickMatch test fails
	*/
	public function testExecutePluginParsersQuickMatchFail()
	{
		$dummy  = new PluginsHandlingDummy;
		$plugin = $this->getMockPlugin($dummy);
		$plugin->expects($this->never())
		       ->method('parse');

		$dummy->pluginParsers['Test'] = [$plugin, 'parse'];
		$dummy->pluginsConfig['Test']['quickMatch'] = '[';

		$dummy->executePluginParsers();
	}

	/**
	* @testdox executePluginParsers() executes a plugin if its regexp test passes
	*/
	public function testExecutePluginParsersRegexpPass()
	{
		$dummy  = new PluginsHandlingDummy('...foo...');
		$plugin = $this->getMockPlugin($dummy);
		$plugin->expects($this->once())
		       ->method('parse');

		$dummy->pluginParsers['Test'] = [$plugin, 'parse'];
		$dummy->pluginsConfig['Test']['regexp'] = '/foo/';
		$dummy->pluginsConfig['Test']['regexpLimit'] = 1000;

		$dummy->executePluginParsers();
	}

	/**
	* @testdox executePluginParsers() does not execute a plugin if its regexp test fails
	*/
	public function testExecutePluginParsersRegexpFail()
	{
		$dummy  = new PluginsHandlingDummy;
		$plugin = $this->getMockPlugin($dummy);
		$plugin->expects($this->never())
		       ->method('parse');

		$dummy->pluginParsers['Test'] = [$plugin, 'parse'];
		$dummy->pluginsConfig['Test']['regexp'] = '/foo/';
		$dummy->pluginsConfig['Test']['regexpLimit'] = 1000;

		$dummy->executePluginParsers();
	}

	/**
	* @testdox executePluginParsers() passes the text and the matches to the plugin's parser
	*/
	public function testExecutePluginParsersArguments()
	{
		$text = '...foo...';
		$matches = [
			[['o', 4]],
			[['o', 5]]
		];

		$dummy  = new PluginsHandlingDummy($text);
		$plugin = $this->getMockPlugin($dummy);
		$plugin->expects($this->once())
		       ->method('parse')
		       ->with($text, $matches);

		$dummy->pluginParsers['Test'] = [$plugin, 'parse'];
		$dummy->pluginsConfig['Test']['regexp'] = '/o/';
		$dummy->pluginsConfig['Test']['regexpLimit'] = 1000;

		$dummy->executePluginParsers();
	}

	/**
	* @testdox executePluginParsers() executes a plugin with the first regexpLimit number of matches if the number of matches exceeds regexpLimit
	*/
	public function testExecutePluginParsersRegexpLimit()
	{
		$text = '...fooo...';
		$matches = [
			[['o', 4]],
			[['o', 5]]
		];

		$dummy  = new PluginsHandlingDummy($text);
		$plugin = $this->getMockPlugin($dummy);
		$plugin->expects($this->once())
		       ->method('parse')
		       ->with($text, $matches);

		$dummy->pluginParsers['Test'] = [$plugin, 'parse'];
		$dummy->pluginsConfig['Test']['regexp'] = '/o/';
		$dummy->pluginsConfig['Test']['regexpLimit'] = 2;

		$dummy->executePluginParsers();
	}

	/**
	* @testdox executePluginParsers() creates an instance of the class name stored in className if present
	*/
	public function testExecutePluginParsersCustomClass()
	{
		$dummy  = new PluginsHandlingDummy('...foo...');
		$plugin = $this->getMockPlugin($dummy);

		$className = get_class($plugin);
		$dummy->pluginsConfig['Test']['className'] = $className;

		$dummy->executePluginParsers();

		$this->assertArrayHasKey('Test', $dummy->pluginParsers);
		$this->assertArrayHasKey(0, $dummy->pluginParsers['Test']);
		$this->assertInstanceOf($className, $dummy->pluginParsers['Test'][0]);
	}

	/**
	* @testdox registerParser() can register a callback that replaces the parser of an existing plugin
	*/
	public function testRegisterParserExisting()
	{
		$dummy  = new PluginsHandlingDummy;
		$parser = $this->getMockBuilder('stdClass')
		               ->setMethods(['foo'])
		               ->getMock();
		$parser->expects($this->once())
		       ->method('foo');

		$dummy->registerParser('Test', [$parser, 'foo']);

		$dummy->executePluginParsers();
	}

	/**
	* @testdox registerParser() can register a callback that acts as the parser of a new plugin
	*/
	public function testRegisterParserNew()
	{
		$dummy  = new PluginsHandlingDummy;
		$dummy->pluginsConfig = [];

		$parser = $this->getMockBuilder('stdClass')
		               ->setMethods(['foo'])
		               ->getMock();
		$parser->expects($this->once())
		       ->method('foo');

		$dummy->registerParser('Foo', [$parser, 'foo']);

		$dummy->executePluginParsers();
	}

	/**
	* @testdox registerParser() throws an exception if its second argument is not callable
	*/
	public function testRegisterParserInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('must be a valid callback');

		$dummy  = new PluginsHandlingDummy;
		$dummy->registerParser('Foo', '*invalid*');
	}

	/**
	* @testdox registerParser() accepts a regexp
	*/
	public function testRegisterParserRegexp()
	{
		$this->configurator->tags->add('X');
		extract($this->configurator->finalize());
		$parser->registerParser(
			'foo',
			function ($text, $matches) use ($parser)
			{
				foreach ($matches as $m)
				{
					$parser->addSelfClosingTag('X', $m[0][1], 1);
				}
			},
			'(x)'
		);
		$this->assertSame(
			'<r>oo<X>x</X>oo<X>x</X>oo</r>',
			$parser->parse('ooxooxoo')
		);
	}

	/**
	* @testdox registerParser() accepts a limit
	*/
	public function testRegisterParserRegexpLimit()
	{
		$this->configurator->tags->add('X');
		extract($this->configurator->finalize());
		$parser->registerParser(
			'foo',
			function ($text, $matches) use ($parser)
			{
				foreach ($matches as $m)
				{
					$parser->addSelfClosingTag('X', $m[0][1], 1);
				}
			},
			'(x)',
			2
		);
		$this->assertSame(
			'<r>oo<X>x</X>oo<X>x</X>ooxoo</r>',
			$parser->parse('ooxooxooxoo')
		);
	}
}

class PluginsHandlingDummy extends Parser
{
	public $logger;
	public $pluginParsers = [];
	public $pluginsConfig = [
		'Test' => [
		]
	];

	public function __construct($text = '')
	{
		$this->text = $text;
	}

	public function executePluginParsers()
	{
		return call_user_func_array(Parser::class . '::executePluginParsers', func_get_args());
	}
}