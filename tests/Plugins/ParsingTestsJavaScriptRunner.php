<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Configurator;

trait ParsingTestsJavaScriptRunner
{
	/**
	* @group needs-js
	* @testdox Parsing tests (JavaScript)
	* @dataProvider getParsingTests
	* @requires extension json
	* @covers s9e\TextFormatter\Configurator\JavaScript
	*/
	public function testJavaScriptParsing($original, $expected, array $pluginOptions = [], $setup = null, $expectedJS = null, $assertMethod = 'assertSame')
	{
		$args = func_get_args();
		if (isset($args[4]))
		{
			// Replace $expected with $expectedJS
			$args[1] = $args[4];
		}

		call_user_func_array([$this, 'testParsing'], $args);
	}
}