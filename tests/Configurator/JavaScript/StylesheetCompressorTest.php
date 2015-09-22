<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\StylesheetCompressor;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\StylesheetCompressor
*/
class StylesheetCompressorTest extends Test
{
	/**
	* @testdox encode() tests
	* @dataProvider getEncodeTests
	*/
	public function testEncode($original, $expected)
	{
		$stylesheetCompressor = new StylesheetCompressor;
		$this->assertSame($expected, $stylesheetCompressor->encode($original));
	}

	public function getEncodeTests()
	{
		$tests = [];
		$dir   = __DIR__ . '/data/StylesheetCompressor/';
		foreach (glob($dir . '*.xsl') as $filepath)
		{
			$tests[] = [file_get_contents($filepath), file_get_contents(substr($filepath, 0, -3) . 'js')];
		}

		return $tests;
	}
}