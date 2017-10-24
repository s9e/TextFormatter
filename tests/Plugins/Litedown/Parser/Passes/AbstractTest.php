<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown\Parser\Passes;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

abstract class AbstractTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	abstract public function getParsingTests();
	abstract public function getRenderingTests();

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

			$callback = (isset($test[3])) ? $test[3] : null;
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