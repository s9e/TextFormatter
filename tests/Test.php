<?php

namespace s9e\Toolkit\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder;

include_once __DIR__ . '/../src/TextFormatter/ConfigBuilder.php';

abstract class Test extends \PHPUnit_Framework_TestCase
{
	public function __get($k)
	{
		switch ($k)
		{
			case 'cb':
				return $this->cb = new ConfigBuilder;

			case 'parser':
				return $this->parser = $this->cb->getParser();

			case 'renderer':
				return $this->renderer = $this->parser->getRenderer();

			default:
				throw new RuntimeException("Bad __get('$k')");
		}
	}

	protected function assertArrayMatches(array $expected, array $actual)
	{
		$this->reduceAndSortArrays($expected, $actual);
		$this->assertSame($expected, $actual);
	}

	protected function reduceAndSortArrays(array &$expected, array &$actual)
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
				$this->reduceAndSortArrays($expected[$k], $v);
			}
		}

		/**
		* Remove null values from $expected, they indicate that the key should NOT appear in $actual
		*/
		foreach (array_keys($expected, null, true) as $k)
		{
			unset($expected[$k]);
		}
	}

	protected function assertArrayHasNestedKeys($array)
	{
		$keys = array_slice(func_get_args(), 1);

		$this->assertInternalType('array', $array);

		foreach ($keys as $key)
		{
			$this->assertArrayHasKey($key, $array);
			$array =& $array[$key];
		}
	}
}