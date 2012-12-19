<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Configurator;

trait RenderingTestsRunner
{
	/**
	* @testdox Parsing+rendering tests
	* @dataProvider getRenderingTests
	*/
	public function testRendering($original, $expected, array $pluginOptions = array(), $setup = null)
	{
		$pluginName = preg_replace('/.*\\\\([^\\\\]+)\\\\.*/', '$1', get_class($this));

		$configurator = new Configurator;
		$configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			$setup($configurator);
		}

		$parser   = $configurator->getParser();
		$renderer = $configurator->getRenderer();

		$this->assertSame($expected, $renderer->render($parser->parse($original)));
	}
}