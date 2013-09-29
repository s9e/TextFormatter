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
			// Links
			[
				'Go to [that site](http://example.org) now!',
				'<rt><p>Go to <URL url="http://example.org"><st>[</st>that site<et>](http://example.org)</et></URL> now!</p></rt>'
			],
			[
				'Go to [that site] (http://example.org) now!',
				'<rt><p>Go to <URL url="http://example.org"><st>[</st>that site<et>] (http://example.org)</et></URL> now!</p></rt>'
			],
			[
				'En route to [Mars](http://en.wikipedia.org/wiki/Mars_(disambiguation\))!',
				'<rt><p>En route to <URL url="http://en.wikipedia.org/wiki/Mars_%28disambiguation%29"><st>[</st>Mars<et>](http://en.wikipedia.org/wiki/Mars_(disambiguation\))</et></URL>!</p></rt>'
			],
			[
				'Go to [\\[x\\[x\\]x\\]](http://example.org/?foo[]=1&bar\\[\\]=1) now!',
				'<rt><p>Go to <URL url="http://example.org/?foo[]=1&amp;bar[]=1"><st>[</st>\\[x\\[x\\]x\\]<et>](http://example.org/?foo[]=1&amp;bar\\[\\]=1)</et></URL> now!</p></rt>'
			],
			// Images
			[
				'.. ![Alt text](http://example.org/img.png) ..',
				'<rt><p>.. <IMG alt="Alt text" src="http://example.org/img.png"><st>![</st>Alt text<et>](http://example.org/img.png)</et></IMG> ..</p></rt>'
			],
			[
				'.. ![Alt text](http://example.org/img.png "Image title") ..',
				'<rt><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image title"><st>![</st>Alt text<et>](http://example.org/img.png "Image title")</et></IMG> ..</p></rt>'
			],
			[
				'.. ![Alt \\[text\\]](http://example.org/img.png "\\"Image title\\"") ..',
				'<rt><p>.. <IMG alt="Alt [text]" src="http://example.org/img.png" title="&quot;Image title&quot;"><st>![</st>Alt \\[text\\]<et>](http://example.org/img.png "\\"Image title\\"")</et></IMG> ..</p></rt>'
			],
			[
				'.. ![Alt text](http://example.org/img.png "Image (title)") ..',
				'<rt><p>.. <IMG alt="Alt text" src="http://example.org/img.png" title="Image (title)"><st>![</st>Alt text<et>](http://example.org/img.png "Image (title)")</et></IMG> ..</p></rt>'
			],
			// Images in links
			[
				'.. [![Alt text](http://example.org/img.png)](http://example.org/) ..',
				'<rt><p>.. <URL url="http://example.org/"><st>[</st><IMG alt="Alt text" src="http://example.org/img.png"><st>![</st>Alt text<et>](http://example.org/img.png)</et></IMG><et>](http://example.org/)</et></URL> ..</p></rt>'
			],
			// Inline code
			[
				'.. `foo` `bar` ..',
				'<rt><p>.. <C><st>`</st>foo<et>`</et></C> <C><st>`</st>bar<et>`</et></C> ..</p></rt>'
			],
			[
				'.. `foo\\` \\`b\\\\ar` ..',
				'<rt><p>.. <C><st>`</st>foo\\` \\`b\\\\ar<et>`</et></C> ..</p></rt>'
			],
			[
				'.. `[foo](http://example.org)` ..',
				'<rt><p>.. <C><st>`</st>[foo](http://example.org)<et>`</et></C> ..</p></rt>'
			],
			[
				'.. `![foo](http://example.org)` ..',
				'<rt><p>.. <C><st>`</st>![foo](http://example.org)<et>`</et></C> ..</p></rt>'
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