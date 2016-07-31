<?php

namespace s9e\TextFormatter\Tests\Plugins\Autoimage;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Autoimage\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Autoimage\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return [
			[
				'.. http://example.org/img.png ..',
				'<r>.. <IMG src="http://example.org/img.png">http://example.org/img.png</IMG> ..</r>'
			],
			[
				'http://example.org/img.png',
				'<r><IMG src="http://example.org/img.png">http://example.org/img.png</IMG></r>'
			],
			[
				'http://example.org/img.png',
				'<r><FOO src="http://example.org/img.png">http://example.org/img.png</FOO></r>',
				['tagName' => 'FOO']
			],
			[
				'http://example.org/img.png',
				'<r><IMG foo="http://example.org/img.png">http://example.org/img.png</IMG></r>',
				['attrName' => 'foo']
			],
			[
				'http://example.org/img.png',
				'<r><IMG src="http://example.org/img.png">http://example.org/img.png</IMG></r>',
				[],
				function ($configurator)
				{
					$configurator->Autolink;
				}
			],
			[
				'.. HTTP://EXAMPLE.ORG/IMG.PNG ..',
				'<r>.. <IMG src="http://EXAMPLE.ORG/IMG.PNG">HTTP://EXAMPLE.ORG/IMG.PNG</IMG> ..</r>'
			],
			[
				'.. http://user:pass@example.org/img.png ..',
				'<t>.. http://user:pass@example.org/img.png ..</t>'
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'http://example.org/img.png',
				'<img src="http://example.org/img.png">'
			],
			[
				'http://example.org/img.png',
				'<img src="http://example.org/img.png">',
				['tagName' => 'FOO']
			],
			[
				'http://example.org/img.png',
				'<img src="http://example.org/img.png">',
				['attrName' => 'foo']
			],
		];
	}
}