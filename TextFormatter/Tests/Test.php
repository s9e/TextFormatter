<?php

namespace s9e\Toolkit\TextFormatter\Tests;

abstract class Test extends \PHPUnit_Framework_TestCase
{
	protected function assertArrayMatches(array $expected, array $actual)
	{
		$this->reduceAndSortArrays($expected, $actual);
		$this->assertSame($expected, $actual);
	}

	protected function reduceAndSortArrays(array &$expected, array &$actual)
	{
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