<?php

namespace s9e\TextFormatter\Tests\Plugins\MarkdownLite;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\MarkdownLite\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MarkdownLite\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
//	use RenderingTestsRunner;

	public function getParsingTests()
	{
		$tests = [
			[
				'Go to [that site](http://example.org) now!',
				'<rt><p>Go to <URL url="http://example.org"><st>[</st>that site<et>](http://example.org)</et></URL> now!</p></rt>'
			],
			[
				'En route to [Mars](http://en.wikipedia.org/wiki/Mars_(disambiguation\))!',
				'<rt><p>En route to <URL url="http://en.wikipedia.org/wiki/Mars_%28disambiguation%29"><st>[</st>Mars<et>](http://en.wikipedia.org/wiki/Mars_(disambiguation\))</et></URL>!</p></rt>'
			],
		];

		foreach ($tests as &$test)
		{
			$test[] = [];
			$test[] = function ($configurator)
			{
				$configurator->addHTML5Rules();
			};
		}

		return $tests;
	}
}