<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser\BuiltInFilters;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\BuiltInFilters
*/
class BuiltInFiltersTestTest extends Test
{
	/**
	* @dataProvider getData
	* @testdox Regression tests
	*/
	public function test($original, array $results)
	{
		foreach ($results as $filterName => $expected)
		{
			$methodName = 'filter' . ucfirst($filterName);
			$testdox = '#' . $filterName;

			if ($expected === false)
			{
				$testdox .= ' rejects ' . var_export($original, true);
			}
			elseif ($expected === $original)
			{
				$testdox .= ' accepts ' . var_export($original, true);
			}
			else
			{
				$testdox .= ' transforms ' . var_export($original, true)
				          . ' into ' . var_export($expected, true);
			}

			$this->assertSame(
				$expected,
				BuiltInFilters::$methodName($original),
				'Failed asserting that ' . $testdox
			);
		}
	}

	/**
	* NOTE: this test is not normative. Some cases exist solely to track regressions
	*/
	public function getData()
	{
		return array(
			array('123', array('int' => 123, 'uint' => 123, 'float' => 123.0, 'number' => '123')),
			array('123abc', array('int' => false, 'uint' => false, 'float' => false, 'number' => false)),
			array('0123', array('int' => false, 'uint' => false, 'float' => 123.0, 'number' => '0123')),
			array('-123', array('int' => -123, 'uint' => false, 'float' => -123.0, 'number' => false)),
			array('12.3', array('int' => false, 'uint' => false, 'float' => 12.3, 'number' => false)),
			array('10000000000000000000', array('int' => false, 'uint' => false, 'float' => 10000000000000000000, 'number' => '10000000000000000000')),
			array('12e3', array('int' => false, 'uint' => false, 'float' => 12000.0, 'number' => false)),
			array('-12e3', array('int' => false, 'uint' => false, 'float' => -12000.0, 'number' => false)),
			array('12e-3', array('int' => false, 'uint' => false, 'float' => 0.012, 'number' => false)),
			array('-12e-3', array('int' => false, 'uint' => false, 'float' => -0.012, 'number' => false)),
			array('0x123', array('int' => false, 'uint' => false, 'float' => false, 'number' => false)),
		);
	}
}