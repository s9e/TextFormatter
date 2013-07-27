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

	protected function assertArrayMatches(array $expected, array $actual, $removeNull = true)
	{
		$this->reduceAndSortArrays($expected, $actual, $removeNull);
		$this->assertSame($expected, $actual);
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

	protected function assertParsing($original, $expected, $setup = null, $callback = null)
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
	}

	protected function execJS($src, $input)
	{
		static $exec, $function;

		if (!isset($exec))
		{
			$interpreters = [
				'd8'   => 'print',
				'node' => 'console.log'
			];

			foreach ($interpreters as $interpreter => $function)
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
			$this->markTestSkipped('No Javascript interpreter available');

			return;
		}

		$src .= ';' . $function . '(parse(' . json_encode($input) . '))';

		return substr(shell_exec($exec . ' -e ' . escapeshellarg($src)), 0, -1);
	}

	protected static $tmpFiles = [];
	public function tempnam($suffix = '.tmp')
	{
		$filepath = sys_get_temp_dir() . '/' . uniqid() . $suffix;
		self::$tmpFiles[] = $filepath;

		return $filepath;
	}
}