<?php

namespace s9e\TextFormatter\Tests\Parser;

use Closure;
use ReflectionClass;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Alnum;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Choice;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Color;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Email;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Float;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Hashmap;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Identifier;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Int;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ip;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipport;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv4;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv6;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Map;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Number;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Range;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Regexp;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Simpletext;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Uint;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Url;
use s9e\TextFormatter\Parser\BuiltInFilters;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\BuiltInFilters
*/
class BuiltInFiltersTest extends Test
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

			$this->assertSame(
				$expected,
				BuiltInFilters::$methodName($original)
			);
		}
	}

	/**
	* @testdox parseUrl() tests
	* @dataProvider getParseUrlTests
	*/
	public function testParseUrl($url, $expected)
	{
		$default = [
			'scheme'   => '',
			'user'     => '',
			'pass'     => '',
			'host'     => '',
			'port'     => '',
			'path'     => '',
			'query'    => '',
			'fragment' => ''
		];

		$this->assertSame(array_merge($default, $expected), BuiltInFilters::parseUrl($url));
	}

	public function getParseUrlTests()
	{
		return [
			[
				'',
				[]
			],
			[
				// parse_url() identifies reddit.com as host, browsers think it's localhost
				'http://localhost#foo@reddit.com/bar',
				[
					'scheme'   => 'http',
					'host'     => 'localhost',
					'fragment' => 'foo@reddit.com/bar'
				]
			],
			[
				'http://@localhost',
				[
					'scheme'   => 'http',
					'host'     => 'localhost'
				]
			],
			[
				'javascript:alert(1)',
				[
					'scheme'   => 'javascript',
					'path'     => 'alert(1)'
				]
			],
			[
				'http://us3r@localhost',
				[
					'scheme'   => 'http',
					'user'     => 'us3r',
					'host'     => 'localhost'
				]
			],
			[
				'http://us3r:p4ss@localhost',
				[
					'scheme'   => 'http',
					'user'     => 'us3r',
					'pass'     => 'p4ss',
					'host'     => 'localhost'
				]
			],
			[
				'http://localhost:80',
				[
					'scheme'   => 'http',
					'host'     => 'localhost',
					'port'     => '80'
				]
			],
			[
				'http://localhost/:80',
				[
					'scheme'   => 'http',
					'host'     => 'localhost',
					'path'     => '/:80'
				]
			],
			[
				'http://localhost/foo?bar=1',
				[
					'scheme'   => 'http',
					'host'     => 'localhost',
					'path'     => '/foo',
					'query'    => 'bar=1'
				]
			],
			[
				'http://localhost/foo#?bar=1',
				[
					'scheme'   => 'http',
					'host'     => 'localhost',
					'path'     => '/foo',
					'fragment' => '?bar=1'
				]
			],
			[
				'http://user@example.org@localhost',
				[
					'scheme'   => 'http',
					'host'     => 'localhost',
					'user'     => 'user@example.org'
				]
			],
			[
				'//example.org/:foo',
				[
					'host'     => 'example.org',
					'path'     => '/:foo'
				]
			],
			[
				'/foo?k=1',
				[
					'path'     => '/foo',
					'query'    => 'k=1'
				]
			],
			[
				'foo?k=1',
				[
					'path'     => 'foo',
					'query'    => 'k=1'
				]
			],
			[
				'#foo',
				[
					'fragment' => 'foo'
				]
			],
			[
				'https://[2001:db8:85a3:8d3:1319:8a2e:370:7348]:443/',
				[
					'scheme'   => 'https',
					'host'     => '[2001:db8:85a3:8d3:1319:8a2e:370:7348]',
					'port'     => '443',
					'path'     => '/'
				]
			],
			[
				'http:///example.org',
				[
					'scheme'   => 'http',
					'host'     => '',
					'path'     => '/example.org'
				]
			],
			[
				'file:///example.org',
				[
					'scheme'   => 'file',
					'host'     => '',
					'path'     => '/example.org'
				]
			],
			[
				'HTTP://example.org',
				[
					'scheme'   => 'http',
					'host'     => 'example.org'
				]
			],
			[
				'http://^*!example.org',
				[
					'scheme'   => 'http',
					'host'     => '^*!example.org'
				]
			],
			[
				'http://www.älypää.com',
				[
					'scheme'   => 'http',
					'host'     => 'www.xn--lyp-plada.com'
				]
			],
			[
				"http://evil\xEF\xBD\xA1example.com.\xEF\xBD\xA1./",
				[
					'scheme'   => 'http',
					'host'     => 'evil.example.com',
					'path'     => '/'
				]
			],
			[
				'mailto:joe@example.org',
				[
					'scheme'   => 'mailto',
					'path'     => 'joe@example.org'
				]
			],
			[
				'0',
				[
					'path'     => '0'
				]
			],
		];
	}

	/**
	* @testdox Filters work
	* @dataProvider getData
	*/
	public function testFilters($filter, $original, $expected, $logs = [], $setup = null)
	{
		$this->configurator
			->tags->add('FOO')
			->attributes->add('foo')
			->filterChain->append($filter);

		if (isset($setup))
		{
			$setup($this->configurator);
		}

		$config = $this->configurator->asConfig();
		ConfigHelper::filterVariants($config);

		$logger = new Logger;

		$parser = new ReflectionClass('s9e\\TextFormatter\\Parser');
		$method = $parser->getMethod('executeFilter');
		$method->setAccessible(true);

		$actual = $method->invoke(
			null,
			$config['tags']['FOO']['attributes']['foo']['filterChain'][0],
			[
				'attrName'       => 'foo',
				'attrValue'      => $original,
				'logger'         => $logger,
				'registeredVars' => $config['registeredVars']
			]
		);

		$this->assertSame($expected, $actual);

		if ($logs instanceof Closure)
		{
			$logs = $logs();
		}

		$this->assertSame($logs, $logger->get(), "Logs don't match");
	}

	/**
	* @testdox filterRange() can be called without a logger
	*/
	public function testRangeNoLogger()
	{
		$this->assertSame(1, BuiltInFilters::filterRange('-10', 1, 5));
		$this->assertSame(5, BuiltInFilters::filterRange('10', 1, 5));
		$this->assertSame(3, BuiltInFilters::filterRange('3', 1, 5));
	}

	/**
	* @testdox filterUrl() can be called without a logger
	*/
	public function testUrlNoLogger()
	{
		$urlConfig = [
			'allowedSchemes'  => '/^https?$/',
			'disallowedHosts' => '/evil/'
		];

		$urls = [
			'http://example.org' => 'http://example.org',
			'http://evil.org'    => false,
			'hax://example.org'  => false
		];

		foreach ($urls as $original => $expected)
		{
			$this->assertSame($expected, BuiltInFilters::filterUrl($original, $urlConfig));
		}
	}

	public function getData()
	{
		return [
			[new Alnum, '', false],
			[new Alnum, 'abcDEF', 'abcDEF'],
			[new Alnum, 'abc_def', false],
			[new Alnum, '0123', '0123'],
			[new Alnum, 'é', false],
			[new Range(2, 5), '2', 2],
			[new Range(2, 5), '5', 5],
			[new Range(-5, 5), '-5', -5],
			[
				new Range(2, 5),
				'1',
				2,
				[
					[
						'warn',
						'Value outside of range, adjusted up to min value',
						['attrValue' => 1, 'min' => 2, 'max' => 5]
					]
				]
			],
			[
				new Range(2, 5),
				'10',
				5,
				[
					[
						'warn',
						'Value outside of range, adjusted down to max value',
						['attrValue' => 10, 'min' => 2, 'max' => 5]
					]
				]
			],
			[new Range(2, 5), '5x', false],
			[
				new Url,
				'http://www.älypää.com',
				'http://www.xn--lyp-plada.com',
				[],
				function ()
				{
					if (!extension_loaded('intl'))
					{
						$this->markTestSkipped('Extension intl is required.');
					}
				}
			],
			[
				new Url,
				'http://en.wikipedia.org/wiki/Matti_Nykänen', 'http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen'
			],
			[
				new Url,
				'http://user:pass@en.wikipedia.org:80/wiki/Matti_Nykänen?foo&bar#baz', 'http://user:pass@en.wikipedia.org:80/wiki/Matti_Nyk%C3%A4nen?foo&bar#baz'
			],
			[
				new Url,
				'http://älypää.com:älypää.com@älypää.com',
				'http://%C3%A4lyp%C3%A4%C3%A4.com:%C3%A4lyp%C3%A4%C3%A4.com@xn--lyp-plada.com',
				[],
				function ()
				{
					if (!extension_loaded('intl'))
					{
						$this->markTestSkipped('Extension intl is required.');
					}
				}
			],
			[new Url, 'javascript:alert()', false],
			[new Url, 'http://www.example.com', 'http://www.example.com'],
			[new Url, '//www.example.com', '//www.example.com'],
			[
				new Url,
				'//www.example.com',
				false,
				[],
				function ($configurator)
				{
					$configurator->urlConfig->requireScheme();
				}
			],
			[new Url, 'HTTP://www.example.com', 'http://www.example.com'],
			[new Url, ' http://www.example.com ', 'http://www.example.com'],
			[new Url, "http://example.com/''", 'http://example.com/%27%27'],
			[new Url, 'http://example.com/""', 'http://example.com/%22%22'],
			[new Url, 'http://example.com/(', 'http://example.com/%28'],
			[new Url, 'http://example.com/)', 'http://example.com/%29'],
			[new Url, "http://example.com/x\0y", 'http://example.com/x%00y'],
			[new Url, "http://example.com/x y", 'http://example.com/x%20y'],
			[new Url, 'http://example.com/foo.php?a[]=1', 'http://example.com/foo.php?a%5B%5D=1'],
			[new Url, 'http://example.com/</script>', 'http://example.com/%3C/script%3E'],
			[
				new Url,
				"http://example.com/\xE2\x80\xA8",
				'http://example.com/%E2%80%A8'
			],
			[
				new Url,
				"http://example.com/\xE2\x80\xA9",
				'http://example.com/%E2%80%A9'
			],
			[
				new Url,
				'ftp://example.com',
				false,
				[
					[
						'err',
						'URL scheme is not allowed',
						[
							'attrValue' => 'ftp://example.com',
							'scheme'    => 'ftp'
						]
					]
				]
			],
			[
				new Url,
				'ftp://example.com',
				'ftp://example.com',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('ftp');
				}
			],
			[
				new Url,
				'mailto:joe@example.com',
				'mailto:joe@example.com',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('mailto');
				}
			],
			[
				new Url,
				'http://evil.example.com',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'attrValue' => 'http://evil.example.com',
							'host'      => 'evil.example.com'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new Url,
				"http://evil\xE3\x80\x82example.com",
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'attrValue' => 'http://evil.example.com',
							'host'      => 'evil.example.com'
						]
					]
				],
				function ($configurator)
				{
					if (!extension_loaded('intl'))
					{
						$this->markTestSkipped('Extension intl is required.');
					}

					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new Url,
				"http://evil\xEF\xBC\x8Eexample.com",
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'attrValue' => 'http://evil.example.com',
							'host'      => 'evil.example.com'
						]
					]
				],
				function ($configurator)
				{
					if (!extension_loaded('intl'))
					{
						$this->markTestSkipped('Extension intl is required.');
					}

					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new Url,
				"http://evil\xEF\xBD\xA1example.com",
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'attrValue' => 'http://evil.example.com',
							'host'      => 'evil.example.com'
						]
					]
				],
				function ($configurator)
				{
					if (!extension_loaded('intl'))
					{
						$this->markTestSkipped('Extension intl is required.');
					}

					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new Url,
				"http://evil.example.com.",
				false,
				function ()
				{
					if (version_compare(PHP_VERSION, '5.5.4', '>=')
					 || (version_compare(PHP_VERSION, '5.4.21', '>=') && version_compare(PHP_VERSION, '5.5', '<')))
					{
						return [
							[
								'err',
								'URL host is not allowed',
								[
									'attrValue' => 'http://evil.example.com.',
									'host'      => 'evil.example.com'
								]
							]
						];
					}

					return [];
				},
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new Url,
				"http://evil\xEF\xBD\xA1example.com\xEF\xBD\xA1",
				false,
				function ()
				{
					if (version_compare(PHP_VERSION, '5.5.4', '>=')
					 || (version_compare(PHP_VERSION, '5.4.21', '>=') && version_compare(PHP_VERSION, '5.5', '<')))
					{
						return [
							[
								'err',
								'URL host is not allowed',
								[
									'attrValue' => 'http://evil.example.com.',
									'host'      => 'evil.example.com'
								]
							]
						];
					}

					return [];
				},
				function ($configurator)
				{
					if (!extension_loaded('intl'))
					{
						$this->markTestSkipped('Extension intl is required.');
					}

					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new Url,
				// NOTE: this URL is actually rejected for being malformed
				"http://evil.ex%41mple.com",
				false,
				[],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new Url,
				'http://www.pаypal.com',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'attrValue' => 'http://www.xn--pypal-4ve.com',
							'host'      => 'www.xn--pypal-4ve.com'
						]
					]
				],
				function ($configurator)
				{
					if (!extension_loaded('intl'))
					{
						$this->markTestSkipped('Extension intl is required.');
					}

					// This is a paypal homograph
					$configurator->urlConfig->disallowHost('pаypal.com');
				}
			],
			[
				new Url,
				'http://www.example.org',
				'http://www.example.org',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			],
			[
				new Url,
				'http://www.example.org',
				'http://www.example.org',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			],
			[
				new Url,
				'http://www.example.org',
				'http://www.example.org',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
					$configurator->urlConfig->restrictHost('example.com');
				}
			],
			[
				new Url,
				'http://evil.example.com',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'attrValue' => 'http://evil.example.com',
							'host'      => 'evil.example.com'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			],
			[
				new Url,
				'http://example.org.example.com',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'attrValue' => 'http://example.org.example.com',
							'host'      => 'example.org.example.com'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			],
			[new Url, 'http:', false],
			[new Url, 'http:?foo', false],
			[new Url, 'http:#foo', false],
			[new Identifier, '123abcABC', '123abcABC'],
			[new Identifier, '-_-', '-_-'],
			[new Identifier, 'a b', false],
			[new Color, '#123abc', '#123abc'],
			[new Color, 'red', 'red'],
			[new Color, 'rgb(12,34,56)', 'rgb(12,34,56)'],
			[new Color, 'rgb(12, 34, 56)', 'rgb(12, 34, 56)'],
			[new Color, '#1234567', false],
			[new Color, 'blue()', false],
			[
				new Simpletext,
				'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+.,_ ', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+.,_ '
			],
			[new Simpletext, 'a()b', false],
			[new Simpletext, 'a[]b', false],
			[new Regexp('/^[A-Z]+$/D'), 'ABC', 'ABC'],
			[new Regexp('/^[A-Z]+$/D'), 'Abc', false],
			[new Email, 'example@example.com', 'example@example.com'],
			[new Email, 'example@example.com()', false],
			[new Map(['uno' => 'one', 'dos' => 'two']), 'dos', 'two'],
			[new Map(['uno' => 'one', 'dos' => 'two']), 'three', 'three'],
			[new Map(['uno' => 'one', 'dos' => 'two'], true, true), 'three', false],
			[new Ip, '8.8.8.8', '8.8.8.8'],
			[new Ip, 'ff02::1', 'ff02::1'],
			[new Ip, 'localhost', false],
			[new Ipv4, '8.8.8.8', '8.8.8.8'],
			[new Ipv4, 'ff02::1', false],
			[new Ipv4, 'localhost', false],
			[new Ipv6, '8.8.8.8', false],
			[new Ipv6, 'ff02::1', 'ff02::1'],
			[new Ipv6, 'localhost', false],
			[new Ipport, '8.8.8.8:80', '8.8.8.8:80'],
			[new Ipport, '[ff02::1]:80', '[ff02::1]:80'],
			[new Ipport, 'localhost:80', false],
			[new Ipport, '[localhost]:80', false],
			[new Ipport, '8.8.8.8', false],
			[new Ipport, 'ff02::1', false],
			[new Hashmap(['foo' => 'bar']), 'foo', 'bar'],
			[new Hashmap(['foo' => 'bar']), 'bar', 'bar'],
			[new Hashmap(['foo' => 'bar'], false), 'bar', 'bar'],
			[new Hashmap(['foo' => 'bar'], true), 'bar', false],
		];
	}

	/**
	* NOTE: this test is not normative. Some cases exist solely to track regressions or changes in
	*       behaviour in ext/filter
	*/
	public function getRegressionsData()
	{
		return [
			['123', ['int' => 123, 'uint' => 123, 'float' => 123.0, 'number' => '123']],
			['123abc', ['int' => false, 'uint' => false, 'float' => false, 'number' => false]],
			['0123', ['int' => false, 'uint' => false, 'float' => 123.0, 'number' => '0123']],
			['-123', ['int' => -123, 'uint' => false, 'float' => -123.0, 'number' => false]],
			['12.3', ['int' => false, 'uint' => false, 'float' => 12.3, 'number' => false]],
			['10000000000000000000', ['int' => false, 'uint' => false, 'float' => 10000000000000000000, 'number' => '10000000000000000000']],
			['12e3', ['int' => false, 'uint' => false, 'float' => 12000.0, 'number' => false]],
			['-12e3', ['int' => false, 'uint' => false, 'float' => -12000.0, 'number' => false]],
			['12e-3', ['int' => false, 'uint' => false, 'float' => 0.012, 'number' => false]],
			['-12e-3', ['int' => false, 'uint' => false, 'float' => -0.012, 'number' => false]],
			['0x123', ['int' => false, 'uint' => false, 'float' => false, 'number' => false]],
		];
	}

	/**
	* @testdox sanitizeUrl() tests
	* @dataProvider getSanitizeUrlTests
	*/
	public function testSanitizeUrl($url, $expected)
	{
		$this->assertSame($expected, BuiltInFilters::sanitizeUrl($url));
	}

	public function getSanitizeUrlTests()
	{
		return [
			[
				"http://example.com/''",
				'http://example.com/%27%27'
			],
			[
				'http://example.com/""',
				'http://example.com/%22%22'
			],
			[
				'http://example.com/((',
				'http://example.com/%28%28'
			],
			[
				'http://example.com/))',
				'http://example.com/%29%29'
			],
			[
				"http://example.com/x\0y",
				'http://example.com/x%00y'
			],
			[
				'http://example.com/x y',
				'http://example.com/x%20y'
			],
			[
				"http://example.com/x\ry",
				'http://example.com/x%0Dy'
			],
			[
				"http://example.com/x\ny",
				'http://example.com/x%0Ay'
			],
			[
				'http://example.com/foo.php?a[]=1',
				'http://example.com/foo.php?a%5B%5D=1'
			],
			[
				'http://example.com/</script>',
				'http://example.com/%3C/script%3E'
			],
			[
				"http://example.com/\xE2\x80\xA8",
				'http://example.com/%E2%80%A8',
			],
			[
				"http://example.com/\xE2\x80\xA8",
				'http://example.com/%E2%80%A8',
			],
			[
				"http://example.com/\xE2\x80\xA9",
				'http://example.com/%E2%80%A9',
			],
			[
				"http://example.com/♥",
				'http://example.com/%E2%99%A5',
			],
		];
	}
}