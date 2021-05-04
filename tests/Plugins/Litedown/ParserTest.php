<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Litedown\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;

	public function getParsingTests()
	{
		return [
			[
				// Ensure that automatic line breaks can be enabled
				"First\nSecond",
				"<t><p>First<br/>\nSecond</p></t>",
				[],
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
				}
			],
		];
	}

	protected static function fixTests($tests)
	{
		foreach ($tests as &$test)
		{
			if (is_array($test[0]))
			{
				$test[0] = implode("\n", $test[0]);
			}

			if (is_array($test[1]))
			{
				$test[1] = implode("\n", $test[1]);
			}

			if (!isset($test[2]))
			{
				$test[2] = [];
			}

			$callback = $test[3] ?? null;
			$test[3] = function ($configurator) use ($callback)
			{
				if (isset($callback))
				{
					$callback($configurator);
				}
			};
		}

		return $tests;
	}
}