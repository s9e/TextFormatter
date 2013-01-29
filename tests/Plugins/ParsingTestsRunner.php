<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Configurator;

trait ParsingTestsRunner
{
	/**
	* @testdox Parsing tests
	* @dataProvider getParsingTests
	*/
	public function testParsing($original, $expected, array $pluginOptions = [], $setup = null)
	{
		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$configurator = new Configurator;
		$configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($configurator);
		}

		$parser = $configurator->getParser();
		$parser->parse($original);

		$this->assertSame($expected, $parser->parse($original));
	}
}