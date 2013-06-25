<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Configurator;

trait ParsingTestsJavaScriptRunner
{
	/**
	* @group needs-js
	* @testdox Parsing tests (JavaScript)
	* @dataProvider getParsingTests
	*/
	public function testJavaScriptParsing($original, $expected, array $pluginOptions = [], $setup = null, $expectedJS = false)
	{
		if ($expectedJS)
		{
			$expected = $expectedJS;
		}

		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$configurator = new Configurator;
		$plugin = $configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($configurator, $plugin);
		}

		$src = $configurator->javascript->getParser();

		$this->assertSame(
			$expected,
			$this->execJS($src, $original)
		);
	}
}