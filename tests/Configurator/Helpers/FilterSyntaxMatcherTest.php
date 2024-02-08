<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\FilterSyntaxMatcher;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\RecursiveParser;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\FilterSyntaxMatcher
*/
class FilterSyntaxMatcherTest extends Test
{
	/**
	* @testdox parse() tests
	* @dataProvider getParseTests
	*/
	public function testParse($filterString, $expected)
	{
		if ($expected instanceof RuntimeException)
		{
			$this->expectException(get_class($expected));
			$this->expectExceptionMessage($expected->getMessage());
		}

		$parser = new RecursiveParser;
		$parser->setMatchers([new FilterSyntaxMatcher($parser)]);

		$this->assertEquals($expected, $parser->parse($filterString)['value']);
	}

	public static function getParseTests()
	{
		return [
			[
				'substr($attrValue, 1 + 1)',
				new RuntimeException('Cannot parse')
			],
			[
				'#int',
				['filter' => '#int']
			],
			[
				'strtolower',
				['filter' => 'strtolower']
			],
			[
				'strtolower($attrValue)',
				[
					'filter' => 'strtolower',
					'params' => [['Name', 'attrValue']]
				]
			],
			[
				'foo\\bar($attrValue)',
				[
					'filter' => 'foo\\bar',
					'params' => [['Name', 'attrValue']]
				]
			],
			[
				'\\foo\\bar::baz($attrValue)',
				[
					'filter' => '\\foo\\bar::baz',
					'params' => [['Name', 'attrValue']]
				]
			],
			[
				'mt_rand()',
				[
					'filter' => 'mt_rand',
					'params' => []
				]
			],
			[
				'mt_rand ( )',
				[
					'filter' => 'mt_rand',
					'params' => []
				]
			],
			[
				'str_replace($attrValue, \'foo\', "bar")',
				[
					'filter' => 'str_replace',
					'params' => [
						['Name',  'attrValue'],
						['Value', 'foo'      ],
						['Value', 'bar'      ]
					]
				]
			],
			[
				'substr($attrValue, 1, -1)',
				[
					'filter' => 'substr',
					'params' => [
						['Name',  'attrValue'],
						['Value', 1          ],
						['Value', -1         ]
					]
				]
			],
			[
				'foo(tRuE, False, nuLL)',
				[
					'filter' => 'foo',
					'params' => [
						['Value', true ],
						['Value', false],
						['Value', null ]
					]
				]
			],
			[
				'foo(0777, 0o777, 0b1101, 0Xcafe, -12.e-34, -1.2e34, .12e34)',
				[
					'filter' => 'foo',
					'params' => [
						['Value', 0777    ],
						['Value', 0777    ],
						['Value', 0b1101  ],
						['Value', 0Xcafe  ],
						['Value', -12.e-34],
						['Value', -1.2e34 ],
						['Value', .12e34  ]
					]
				]
			],
			[
				'foo(6.674_083e-11, 299_792_458, 0xCAFE_F00D, 0b0101_1111, 0137_041, 1_0.1_1e1_0)',
				[
					'filter' => 'foo',
					'params' => [
						['Value', 6.674083e-11],
						['Value', 299792458   ],
						['Value', 0xCAFEF00D  ],
						['Value', 0b01011111  ],
						['Value', 0137041     ],
						['Value', 10.11e10    ]
					]
				]
			],
			// https://wiki.php.net/rfc/numeric_literal_separator#restrictions
			[
				'foo(_100)',
				new RuntimeException('Cannot parse')
			],
			[
				'foo(100_)',
				new RuntimeException('Cannot parse')
			],
			[
				'foo(1__1)',
				new RuntimeException('Cannot parse')
			],
			[
				'foo(1_.0)',
				new RuntimeException('Cannot parse')
			],
			[
				'foo(1._0)',
				new RuntimeException('Cannot parse')
			],
			[
				'foo(0x_123)',
				new RuntimeException('Cannot parse')
			],
			[
				'foo(0b_101)',
				new RuntimeException('Cannot parse')
			],
			[
				'foo(1_e2)',
				new RuntimeException('Cannot parse')
			],
			[
				'foo(1e_2)',
				new RuntimeException('Cannot parse')
			],
			[
				'foo(1.5, -.5)',
				[
					'filter' => 'foo',
					'params' => [
						['Value', 1.5],
						['Value', -.5]
					]
				]
			],
			[
				'strtr($attrValue, "\\x00\\r\\n", "   ")',
				[
					'filter' => 'strtr',
					'params' => [
						['Name',  'attrValue'],
						['Value', "\0\r\n"   ],
						['Value', '   '      ]
					]
				]
			],
			[
				'foo("\\n\\r\\t\\v\\e\\f", "\\\\", "\\$", "\\"", "\\012", "\\77", "\\x40", "\\u{0040}")',
				[
					'filter' => 'foo',
					'params' => [
						['Value', "\n\r\t\v\e\f"],
						['Value', "\\"],
						['Value', "\$"],
						['Value', "\""],
						['Value', "\012"],
						['Value', "\77"],
						['Value', "\x40"],
						['Value', "\u{0040}"]
					]
				]
			],
			[
				"str_replace('\\\\\\'', '\\'', \$attrValue)",
				[
					'filter' => 'str_replace',
					'params' => [
						['Value', "\\'"      ],
						['Value', "'"        ],
						['Name',  'attrValue']
					]
				]
			],
			[
				'preg_replace(/foo/, "", $attrValue)',
				[
					'filter' => 'preg_replace',
					'params' => [
						['Value', new Regexp('/foo/', true)],
						['Value', ''                       ],
						['Name',  'attrValue'              ]
					]
				]
			],
			[
				'preg_replace(/foo/gis, "", $attrValue)',
				[
					'filter' => 'preg_replace',
					'params' => [
						['Value', new Regexp('/foo/is', true)],
						['Value', ''                         ],
						['Name',  'attrValue'                ]
					]
				]
			],
			[
				"foo([])",
				[
					'filter' => 'foo',
					'params' => [['Value', []]]
				]
			],
			[
				"foo([null => null])",
				[
					'filter' => 'foo',
					'params' => [['Value', [null => null]]]
				]
			],
			[
				"foo([1, 2])",
				[
					'filter' => 'foo',
					'params' => [['Value', [1, 2]]]
				]
			],
			[
				"foo(['foo' => 123, 'bar' => 456])",
				[
					'filter' => 'foo',
					'params' => [['Value', ['foo' => 123, 'bar' => 456]]]
				]
			],
			[
				"foo([123 => [3,4], 789])",
				[
					'filter' => 'foo',
					'params' => [['Value', [123 => [3, 4], 789]]]
				]
			],
			[
				"foo([1 => 1, 0 => 0])",
				[
					'filter' => 'foo',
					'params' => [['Value', [1 => 1, 0 => 0]]]
				]
			],
			[
				'foo([$attrValue])',
				new RuntimeException("Cannot parse 'foo([\$attrValue])'")
			],
			[
				'#regexp($attrValue, /^\\d+$/)',
				[
					'filter' => '#regexp',
					'params' => [
						['Name', 'attrValue'],
						['Value', new Regexp('/^\\d+$/', true)]
					]
				]
			],
		];
	}
}