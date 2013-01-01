<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Configurator;

trait ParsingTestsJavascriptRunner
{
	/**
	* @testdox Parsing tests (Javascript)
	* @dataProvider getParsingTests
	*/
	public function testJavascriptParsing($original, $expected, array $pluginOptions = array(), $setup = null, $skip = false)
	{
		if ($skip)
		{
			$this->markTestSkipped($skip);

			return;
		}

		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$configurator = new Configurator;
		$configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($configurator);
		}

		$src = $configurator->javascript->getParser();
		$src .= ';console.log(parse(' . json_encode($original) . '))';

		$this->assertSame(
			$expected,
			substr(shell_exec('node -e ' . escapeshellarg($src)), 0, -1)
		);
	}
}