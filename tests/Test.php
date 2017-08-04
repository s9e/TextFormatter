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
		if (isset($setup))
		{
			call_user_func($setup, $this->configurator);
		}

		$parser = $this->getParser($this->configurator);
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

	protected function assertJSParsing($original, $expected, $assertMethod = 'assertSame')
	{
		$this->configurator->enableJavaScript();

		// Minify and cache the parser if we have a cache dir and we're not on Travis
		$cacheDir = __DIR__ . '/.cache';
		if (empty($_SERVER['TRAVIS']) && file_exists($cacheDir))
		{
			$closureCompilerBin = $this->getClosureCompilerBin();

			if ($closureCompilerBin !== false)
			{
				$this->configurator->javascript
					->setMinifier('ClosureCompilerApplication', $closureCompilerBin)
					->cacheDir = $cacheDir;
			}
		}

		$this->configurator->javascript->exportMethods = ['parse'];
		$objects = $this->configurator->finalize();
		$src     = $objects['js'];

		$this->$assertMethod($expected, $this->execJS($src, $original));
	}

	public function getClosureCompilerBin()
	{
		static $filepath;

		if (!isset($filepath))
		{
			$filepath = false;
			$paths = [
				'/usr/local/bin/compiler.jar',
				'/usr/bin/compiler.jar',
				'/tmp/compiler.jar'
			];

			foreach ($paths as $path)
			{
				if (file_exists($path))
				{
					$filepath = $path;
					break;
				}
			}
		}

		return $filepath;
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
		$filepath = $this->tempnam();
		file_put_contents($filepath, $src);

		return substr(shell_exec($exec . $options . ' ' . escapeshellarg($filepath)), 0, -1);
	}

	protected static $tmpFiles = [];
	public function tempnam()
	{
		$filepath = sys_get_temp_dir() . '/s9e_' . uniqid() . '.tmp';
		self::$tmpFiles[] = $filepath;

		return $filepath;
	}

	protected function runClosure($closure)
	{
		return $closure();
	}

	protected static function ws($template)
	{
		return preg_replace('(>\\n\\s*<)', '><', trim($template));
	}

	protected function getParser($configurator = null)
	{
		if (!isset($configurator))
		{
			$configurator = $this->configurator;
		}
		$objects = $configurator->finalize();

		return $objects['parser'];
	}
}