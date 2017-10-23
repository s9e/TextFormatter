<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Configurator;

trait ParsingTestsRunner
{
	/**
	* @testdox Parsing tests
	* @dataProvider getParsingTests
	*/
	public function testParsing($original, $expected, array $pluginOptions = [], $setup = null, $expectedJS = null, $assertMethod = 'assertSame')
	{
		$pluginName = preg_replace('/^.*?\\\\Plugins\\\\([^\\\\]++).*/', '$1', get_class($this));
		$plugin     = $this->configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($this->configurator, $plugin);
		}

		$this->$assertMethod($expected, $this->getParser()->parse($original));
	}
}