<?php

namespace s9e\TextFormatter\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use s9e\TextFormatter\Configurator;

abstract class Test extends TestCase
{
	protected Configurator $configurator;

	protected function setUp(): void
	{
		$this->configurator = new Configurator;
	}

	public function __call($methodName, $args)
	{
		// Compatibility map for PHPUnit <9
		$map = [
			'assertDoesNotMatchRegularExpression' => 'assertNotRegExp',
			'assertFileDoesNotExist'              => 'assertFileNotExists',
			'assertMatchesRegularExpression'      => 'assertRegExp'
		];
		if (!isset($map[$methodName]))
		{
			throw new RuntimeException("Unknown method '$methodName'");
		}

		return call_user_func_array([$this, $map[$methodName]], $args);
	}

	public static function tearDownAfterClass(): void
	{
		foreach (self::$tmpFiles as $filepath)
		{
			if (file_exists($filepath))
			{
				unlink($filepath);
			}
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
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());

		}

		$this->assertSame($expected, $parser->parse($original));

		if (isset($expectedLogs))
		{
			$this->assertArrayMatches($expectedLogs, $parser->getLogger()->getLogs(), true, 'assertEquals');
		}
	}

	protected function assertJSParsing($original, $expected, $assertMethod = 'assertSame')
	{
		$this->configurator->enableJavaScript();

		// Minify and cache the parser if we have a cache dir and we're not on Travis
		$cacheDir = __DIR__ . '/.cache';
		if (empty($_SERVER['TRAVIS']) && file_exists($cacheDir))
		{
			$closureCompilerNative = $this->getClosureCompilerNative();

			if ($closureCompilerNative !== false)
			{
				$minifier = $this->configurator->javascript->setMinifier('ClosureCompilerApplication', $closureCompilerNative);
				$minifier->cacheDir = $cacheDir;
				$minifier->options .= ' --jscomp_error "*" --jscomp_off "strictCheckTypes"';
			}
		}

		$this->configurator->javascript->exports = ['parse'];
		$objects = $this->configurator->finalize();
		$src     = $objects['js'];

		$this->$assertMethod($expected, $this->execJS($src, $original));
	}

	public function getClosureCompilerNative()
	{
		$filepath = __DIR__ . '/../vendor/node_modules/google-closure-compiler-linux/compiler';

		return (file_exists($filepath)) ? $filepath : false;
	}

	protected function execJS($src, $input)
	{
		static $exec, $function, $options;

		if (!isset($exec))
		{
			$interpreters = [
				// duktape 2.7.0 does not support ES6
				//'duk'  => ['print', ''],
				'qjs'  => ['print', ''],
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

	protected function getPluginName()
	{
		return preg_replace('/^.*?\\\\Plugins\\\\([^\\\\]++).*/', '$1', get_class($this));
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

	protected function getObjectProperty($object, $propName)
	{
		$class = new ReflectionClass(get_class($object));
		$prop  = $class->getProperty($propName);
		$prop->setAccessible(true);

		return $prop->getValue($object);
	}
}