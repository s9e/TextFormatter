<?php

namespace s9e\TextFormatter\Tests;

use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator;

abstract class Test extends \PHPUnit_Framework_TestCase
{
	public static function tearDownAfterClass()
	{
		foreach (self::$tmpFiles as $filepath)
		{
			if (file_exists($filepath))
			{
				unlink($filepath);
			}
		}
	}

	public function __get($k)
	{
		switch ($k)
		{
			case 'configurator':
				return $this->configurator = new Configurator;

			default:
				throw new RuntimeException("Bad __get('$k')");
		}
	}

	protected function assertArrayMatches(array $expected, array $actual, $removeNull = true, $methodName = 'assertSame')
	{
		$this->reduceAndSortArrays($expected, $actual, $removeNull);
		$this->$methodName($expected, $actual);
	}

	protected function reduceAndSortArrays(array &$expected, array &$actual, $removeNull = true)
	{
		if (empty($expected))
		{
			return;
		}

		ksort($expected);
		ksort($actual);

		$actual = array_intersect_key($actual, $expected);

		foreach ($actual as $k => &$v)
		{
			if (is_array($expected[$k]) && is_array($v))
			{
				$this->reduceAndSortArrays($expected[$k], $v, $removeNull);
			}
		}

		// Remove null values from $expected, they indicate that the key should NOT appear in
		// $actual
		if ($removeNull)
		{
			foreach (array_keys($expected, null, true) as $k)
			{
				unset($expected[$k]);
			}
		}
	}

	protected function assertParsing($original, $expected, $setup = null, $callback = null, array $expectedLogs = null)
	{
		$configurator = new Configurator;

		if (isset($setup))
		{
			call_user_func($setup, $configurator);
		}

		$parser = $configurator->getParser();
		$parser->registerParser(
			'Test',
			function () use ($callback, $parser)
			{
				if (isset($callback))
				{
					call_user_func($callback, $parser);
				}
			}
		);

		if ($expected instanceof Exception)
		{
			$this->setExpectedException(get_class($expected), $expected->getMessage());
		}

		$this->assertSame($expected, $parser->parse($original));

		if (isset($expectedLogs))
		{
			$this->assertArrayMatches($expectedLogs, $parser->getLogger()->get(), true, 'assertEquals');
		}
	}

	protected function assertJSParsing($original, $expected)
	{
		// Minify and cache the parser if we have a cache dir
		$cacheDir           = __DIR__ . '/.cache';
		$closureCompilerBin = $this->getClosureCompilerBin();

		if (file_exists($cacheDir) && file_exists($closureCompilerBin) && empty($_SERVER['TRAVIS']))
		{
			$this->configurator->javascript
				->setMinifier('ClosureCompilerApplication', $closureCompilerBin)
				->cacheDir = $cacheDir;
		}

		$this->configurator->javascript->exportMethods = ['parse'];
		$src = $this->configurator->javascript->getParser();

		$this->assertSame(
			$expected,
			$this->execJS($src, $original)
		);
	}

	public function getClosureCompilerBin()
	{
		return (isset($_SERVER['CLOSURE_COMPILER'])) ? $_SERVER['CLOSURE_COMPILER'] : '/usr/local/bin/compiler.jar';
	}

	protected function execJS($src, $input)
	{
		static $exec, $function, $options;

		if (!isset($exec))
		{
			$interpreters = [
				'js17' => ['print', ' -U'],
				'd8'   => ['print', ''],
				'node' => ['console.log', '']
			];

			foreach ($interpreters as $interpreter => list($function, $options))
			{
				$exec = trim(shell_exec('which ' . $interpreter . ' 2> /dev/null'));

				if ($exec)
				{
					break;
				}
			}
		}

		if (!$exec)
		{
			$this->markTestSkipped('No JavaScript interpreter available');

			return;
		}

		$src = file_get_contents(__DIR__ . '/browserStub.js') . $src . ';' . $function . '(window.s9e.TextFormatter.parse(' . json_encode($input) . '))';

		return substr(shell_exec($exec . $options . ' -e ' . escapeshellarg($src)), 0, -1);
	}

	protected static $tmpFiles = [];
	public function tempnam($suffix = '.tmp')
	{
		$filepath = sys_get_temp_dir() . '/' . uniqid() . $suffix;
		self::$tmpFiles[] = $filepath;

		return $filepath;
	}

	protected function runClosure($closure)
	{
		return $closure();
	}
}