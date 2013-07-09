<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Configurator;

trait ParsingTestsJavaScriptRunner
{
	/**
	* @group needs-js
	* @testdox Parsing tests (JavaScript)
	* @dataProvider getParsingTests
	* @covers s9e\TextFormatter\Configurator\JavaScript
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

		// Replace the html_entity_decode() so that it doesn't require the document object. This is
		// a stub implementation, barely good enough for our tests
		$src = preg_replace(
			'#\\nfunction html_entity_decode.*?\\n}#s',
			"
				/**
				* @param  {!string} str
				* @return {!string}
				*/
				function html_entity_decode(str)
				{
					return str.replace(
						/&[^;]+;/g,
						function (entity)
						{
							var table = {
								'&lt;'     : '<',
								'&gt;'     : '>',
								'&amp;'    : '&',
								'&quot;'   : '\"',
								'&hearts;' : '♥',
								'&#x2665;' : '♥',
								'&#9829;'  : '♥'
							};

							return (entity in table) ? table[entity] : entity;
						}
					);
				}
			",
			$src
		);

		$this->assertSame(
			$expected,
			$this->execJS($src, $original)
		);
	}
}