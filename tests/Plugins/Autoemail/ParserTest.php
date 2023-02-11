<?php

namespace s9e\TextFormatter\Tests\Plugins\Autoemail;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Autoemail\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Autoemail\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public static function getParsingTests()
	{
		return [
			[
				'Hit me at example@example.com',
				'<r>Hit me at <EMAIL email="example@example.com">example@example.com</EMAIL></r>'
			],
			[
				'Hit me at example@example.com.',
				'<r>Hit me at <EMAIL email="example@example.com">example@example.com</EMAIL>.</r>'
			],
			[
				'Hit me at example@example.com',
				'<r>Hit me at <FOO email="example@example.com">example@example.com</FOO></r>',
				['tagName' => 'FOO']
			],
			[
				'Hit me at example@example.com',
				'<r>Hit me at <EMAIL bar="example@example.com">example@example.com</EMAIL></r>',
				['attrName' => 'bar']
			],
			[
				'Twit me at @foo.bar',
				'<t>Twit me at @foo.bar</t>'
			],
		];
	}

	public static function getRenderingTests()
	{
		return [
			[
				'Hit me at example@example.com',
				'Hit me at <a href="mailto:example@example.com">example@example.com</a>'
			],
			[
				'Hit me at example@example.com',
				'Hit me at <a href="mailto:example@example.com">example@example.com</a>',
				['tagName' => 'FOO']
			],
			[
				'Hit me at example@example.com',
				'Hit me at <a href="mailto:example@example.com">example@example.com</a>',
				['tagName' => 'FOO']
			],
		];
	}
}