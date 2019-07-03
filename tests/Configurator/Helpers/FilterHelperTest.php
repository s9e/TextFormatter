<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use Exception;
use s9e\TextFormatter\Configurator\Helpers\FilterHelper;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\FilterHelper
*/
class FilterHelperTest extends Test
{
	/**
	* @testdox isAllowed() tests
	* @dataProvider getIsAllowedTests
	*/
	public function testIsAllowed(string $filter, array $allowed, bool $expected)
	{
		$this->assertEquals($expected, FilterHelper::isAllowed($filter, $allowed));
	}

	public function getIsAllowedTests()
	{
		return [
			[
				// Default filter are always allowed
				'#int',
				[],
				true
			],
			[
				'strtolower',
				['strtolower'],
				true
			],
			[
				'\\strtolower',
				['strtolower'],
				true
			],
			[
				'strtolower ( $attrValue )',
				['strtolower'],
				true
			],
			[
				'strtoupper',
				['strtolower'],
				false
			],
			[
				'\\Foo::bar(1)',
				['strtolower', 'Foo::bar'],
				true
			],
		];
	}

	/**
	* @testdox parse() tests
	* @dataProvider getParseTests
	*/
	public function testParse($filterString, $expected)
	{
		if ($expected instanceof Exception)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());
		}

		$this->assertEquals($expected, FilterHelper::parse($filterString));
	}

	public function getParseTests()
	{
		return [
			[
				'#int',
				['filter' => '#int']
			],
			[
				'strtolower',
				['filter' => 'strtolower']
			],
			[
				'\\foo\\bar::baz($attrValue)',
				[
					'filter' => 'foo\\bar::baz',
					'params' => [['Name', 'attrValue']]
				]
			],
			[
				'foo([$attrValue])',
				new Exception('Cannot parse')
			],
		];
	}
}