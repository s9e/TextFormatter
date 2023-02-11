<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\FloatFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\IntFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\NumberFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\UintFilter;
use s9e\TextFormatter\Parser\AttributeFilters\NumericFilter;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\NumericFilter
*/
class NumericFilterTest extends AbstractFilterTestClass
{
	/**
	* @dataProvider getRegressionsData
	* @testdox Regression tests
	*/
	public function testRegressions($original, array $results)
	{
		foreach ($results as $filterName => $expected)
		{
			$methodName = 'filter' . ucfirst($filterName);
			$this->assertSame($expected, NumericFilter::$methodName($original));
		}
	}

	/**
	* NOTE: this test is not normative. Some cases exist solely to track regressions or changes in
	*       behaviour in ext/filter
	*/
	public static function getRegressionsData()
	{
		return [
			['123',    ['int' => 123,   'uint' => 123,   'float' => 123.0]],
			['123abc', ['int' => false, 'uint' => false, 'float' => false]],
			['0123',   ['int' => false, 'uint' => false, 'float' => 123.0]],
			['-123',   ['int' => -123,  'uint' => false, 'float' => -123.0]],
			['12.3',   ['int' => false, 'uint' => false, 'float' => 12.3]],
			['10000000000000000000', ['int' => false, 'uint' => false, 'float' => 10000000000000000000]],
			['12e3',   ['int' => false, 'uint' => false, 'float' => 12000.0]],
			['-12e3',  ['int' => false, 'uint' => false, 'float' => -12000.0]],
			['12e-3',  ['int' => false, 'uint' => false, 'float' => 0.012]],
			['-12e-3', ['int' => false, 'uint' => false, 'float' => -0.012]],
			['0x123',  ['int' => false, 'uint' => false, 'float' => false]],
		];
	}

	public static function getFilterTests()
	{
		return [
			[new FloatFilter,  '123',     123  ],
			[new FloatFilter,  '123.1',   123.1],
			[new FloatFilter,  '123.1.2', false],
			[new IntFilter,    '0',       0    ],
			[new IntFilter,    '123',     123  ],
			[new IntFilter,    '-123',    -123 ],
			[new IntFilter,    '123.1',   false],
			[new NumberFilter, '0',       0    ],
			[new NumberFilter, '123',     123  ],
			[new NumberFilter, '012',     '012'],
			[new NumberFilter, '123x',    false],
			[new UintFilter,   '0',       0    ],
			[new UintFilter,   '123',     123  ],
			[new UintFilter,   '-123',    false],
			[new UintFilter,   '123.1',   false],
		];
	}
}