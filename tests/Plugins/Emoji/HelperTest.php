<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoji;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Emoji\Helper;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoji\Helper
*/
class HelperTest extends Test
{
	/**
	* @testdox Converts all emoji
	*/
	public function test()
	{
		$original = file_get_contents(__DIR__ . '/all.txt');
		$expected = file_get_contents(__DIR__ . '/all.html');
		$modified = Helper::toShortName($original);

		$this->assertNotSame($modified, $original);
		$this->assertRegexp('(^[-+_a-z0-9:\\n]++$)D', $modified);

		$this->configurator->Emoji->setRegexpLimit(10000);
		$this->configurator->Emoji->getTag()->tagLimit = 10000;
		extract($this->configurator->finalize());

		$xml  = $parser->parse($modified);
		$html = $renderer->render($xml);
		$this->assertEquals(
			// Remove the alt attribute because it contains the original emoji in all.html
			preg_replace('( alt="[^"]*")', '', $expected),
			preg_replace('( alt="[^"]*")', '', $html)
		);
	}
}