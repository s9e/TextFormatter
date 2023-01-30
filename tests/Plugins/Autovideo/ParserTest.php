<?php

namespace s9e\TextFormatter\Tests\Plugins\Autovideo;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Autovideo\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\AbstractStaticUrlReplacer\AbstractParser
* @covers s9e\TextFormatter\Plugins\Autovideo\Parser
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
				'.. http://example.org/vid.mp4 ..',
				'<r>.. <VIDEO src="http://example.org/vid.mp4">http://example.org/vid.mp4</VIDEO> ..</r>'
			],
			[
				'http://example.org/vid.mp4',
				'<r><VIDEO src="http://example.org/vid.mp4">http://example.org/vid.mp4</VIDEO></r>'
			],
			[
				'http://example.org/vid.mp4',
				'<r><FOO src="http://example.org/vid.mp4">http://example.org/vid.mp4</FOO></r>',
				['tagName' => 'FOO']
			],
			[
				'http://example.org/vid.mp4',
				'<r><VIDEO foo="http://example.org/vid.mp4">http://example.org/vid.mp4</VIDEO></r>',
				['attrName' => 'foo']
			],
			[
				'http://example.org/vid.mp4',
				'<r><VIDEO src="http://example.org/vid.mp4"><URL url="http://example.org/vid.mp4">http://example.org/vid.mp4</URL></VIDEO></r>',
				[],
				function ($configurator)
				{
					$configurator->Autolink;
				}
			],
			[
				'.. HTTP://EXAMPLE.ORG/VIDEO.OGG ..',
				'<r>.. <VIDEO src="http://EXAMPLE.ORG/VIDEO.OGG">HTTP://EXAMPLE.ORG/VIDEO.OGG</VIDEO> ..</r>'
			],
			[
				'.. http://user:pass@example.org/vid.mp4 ..',
				'<t>.. http://user:pass@example.org/vid.mp4 ..</t>'
			],
			[
				'.. http://example.org/my%20video%20(1).mp4 ..',
				'<r>.. <VIDEO src="http://example.org/my%20video%20%281%29.mp4">http://example.org/my%20video%20(1).mp4</VIDEO> ..</r>'
			],
			[
				'.. http://example.org/2017-01-01_12:34.mp4 ..',
				'<r>.. <VIDEO src="http://example.org/2017-01-01_12:34.mp4">http://example.org/2017-01-01_12:34.mp4</VIDEO> ..</r>'
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'http://example.org/vid.mp4',
				'<video controls="" src="http://example.org/vid.mp4"></video>'
			],
			[
				'http://example.org/vid.mp4',
				'<video controls="" src="http://example.org/vid.mp4"></video>',
				['tagName' => 'FOO']
			],
			[
				'http://example.org/vid.mp4',
				'<video controls="" src="http://example.org/vid.mp4"></video>',
				['attrName' => 'foo']
			],
		];
	}
}