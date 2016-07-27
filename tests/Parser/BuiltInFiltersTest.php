<?php

namespace s9e\TextFormatter\Tests\Parser;

use Closure;
use ReflectionClass;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\AlnumFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\ChoiceFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\ColorFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\EmailFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\FalseFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\FloatFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\HashmapFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\IdentifierFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\IntFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\IpFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\IpportFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv4Filter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Ipv6Filter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\MapFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\NumberFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\RangeFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\RegexpFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\SimpletextFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\UintFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
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

		$config = ConfigHelper::filterConfig($this->configurator->asConfig(), 'PHP');

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
			[new AlnumFilter, '', false],
			[new AlnumFilter, 'abcDEF', 'abcDEF'],
			[new AlnumFilter, 'abc_def', false],
			[new AlnumFilter, '0123', '0123'],
			[new AlnumFilter, 'é', false],
			[new RangeFilter(2, 5), '2', 2],
			[new RangeFilter(2, 5), '5', 5],
			[new RangeFilter(-5, 5), '-5', -5],
			[
				new RangeFilter(2, 5),
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
				new RangeFilter(2, 5),
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
			[new RangeFilter(2, 5), '5x', false],
			[
				new UrlFilter,
				'http://www.älypää.com',
				'http://www.xn--lyp-plada.com',
				[],
				function ()
				{
					if (!function_exists('idn_to_ascii'))
					{
						$this->markTestSkipped('idn_to_ascii() is required.');
					}
				}
			],
			[
				new UrlFilter,
				'http://en.wikipedia.org/wiki/Matti_Nykänen', 'http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen'
			],
			[
				new UrlFilter,
				'https://[2001:db8:85a3:8d3:1319:8a2e:370:7348]:443/', 'https://[2001:db8:85a3:8d3:1319:8a2e:370:7348]:443/'
			],
			[
				new UrlFilter,
				'http://127.0.0.1:80/',
				'http://127.0.0.1:80/'
			],
			[new UrlFilter, '//foo', '//foo'],
			[new UrlFilter, '/foo', '/foo'],
			[new UrlFilter, '?foo', '?foo'],
			[new UrlFilter, '?', '?'],
			[new UrlFilter, '#bar', '#bar'],
			[new UrlFilter, '#', '#'],
			[new UrlFilter, '://bar', '%3A//bar'],
			[new UrlFilter, '*://bar', '*%3A//bar'],
			[new UrlFilter, '/:foo/:bar', '/:foo/:bar'],
			[
				new UrlFilter,
				'http://user:pass@en.wikipedia.org:80/wiki/Matti_Nykänen?foo&bar#baz', 'http://user:pass@en.wikipedia.org:80/wiki/Matti_Nyk%C3%A4nen?foo&bar#baz'
			],
			[
				new UrlFilter,
				'http://älypää.com:älypää.com@älypää.com',
				'http://%C3%A4lyp%C3%A4%C3%A4.com:%C3%A4lyp%C3%A4%C3%A4.com@xn--lyp-plada.com',
				[],
				function ()
				{
					if (!function_exists('idn_to_ascii'))
					{
						$this->markTestSkipped('idn_to_ascii() is required.');
					}
				}
			],
			[
				new UrlFilter,
				'javascript:alert()',
				false,
				[
					[
						'err',
						'URL scheme is not allowed',
						[
							'scheme'    => 'javascript',
							'user'      => '',
							'pass'      => '',
							'host'      => '',
							'port'      => '',
							'path'      => 'alert()',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'javascript:alert()'
						]
					]
				]
			],
			[new UrlFilter, 'http://www.example.com', 'http://www.example.com'],
			[new UrlFilter, '//www.example.com', '//www.example.com'],
			[new UrlFilter, 'HTTP://www.example.com', 'http://www.example.com'],
			[new UrlFilter, 'http://rfc.1123.example.com', 'http://rfc.1123.example.com'],
			[new UrlFilter, 'http://localhost/', 'http://localhost/'],
			[new UrlFilter, 'http://127.0.0.1/', 'http://127.0.0.1/'],
			[new UrlFilter, ' http://www.example.com ', 'http://www.example.com'],
			[new UrlFilter, "http://example.com/''", 'http://example.com/%27%27'],
			[new UrlFilter, 'http://example.com/""', 'http://example.com/%22%22'],
			[new UrlFilter, 'http://example.com/(', 'http://example.com/%28'],
			[new UrlFilter, 'http://example.com/)', 'http://example.com/%29'],
			[new UrlFilter, "http://example.com/x\0y", 'http://example.com/x%00y'],
			[new UrlFilter, "http://example.com/x y", 'http://example.com/x%20y'],
			[new UrlFilter, 'http://example.com/foo.php?a[]=1', 'http://example.com/foo.php?a%5B%5D=1'],
			[new UrlFilter, 'http://example.com/</script>', 'http://example.com/%3C/script%3E'],
			[
				new UrlFilter,
				"http://example.com/\xE2\x80\xA8",
				'http://example.com/%E2%80%A8'
			],
			[
				new UrlFilter,
				"http://example.com/\xE2\x80\xA9",
				'http://example.com/%E2%80%A9'
			],
			[
				new UrlFilter,
				'ftp://example.com',
				false,
				[
					[
						'err',
						'URL scheme is not allowed',
						[
							'scheme'    => 'ftp',
							'user'      => '',
							'pass'      => '',
							'host'      => 'example.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'ftp://example.com'
						]
					]
				]
			],
			[
				new UrlFilter,
				'ftp://example.com',
				'ftp://example.com',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('ftp');
				}
			],
			[
				new UrlFilter,
				'http://evil.example.com',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'evil.example.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'http://evil.example.com'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new UrlFilter,
				'//evil.example.com',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => '',
							'user'      => '',
							'pass'      => '',
							'host'      => 'evil.example.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => '//evil.example.com'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new UrlFilter,
				"http://evil\xE3\x80\x82example.com",
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'evil.example.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => "http://evil\xE3\x80\x82example.com"
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new UrlFilter,
				"http://evil\xEF\xBC\x8Eexample.com",
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'evil.example.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => "http://evil\xEF\xBC\x8Eexample.com"
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new UrlFilter,
				"http://evil\xEF\xBD\xA1example.com",
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'evil.example.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => "http://evil\xEF\xBD\xA1example.com"
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new UrlFilter,
				"http://evil.example.com.",
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'evil.example.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => "http://evil.example.com."
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new UrlFilter,
				"http://evil\xEF\xBD\xA1example.com\xEF\xBD\xA1",
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'evil.example.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => "http://evil\xEF\xBD\xA1example.com\xEF\xBD\xA1"
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new UrlFilter,
				"http://evil.ex%41mple.com",
				false,
				[
					[
						'err',
						'URL host is invalid',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'evil.ex%41mple.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'http://evil.ex%41mple.com'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			],
			[
				new UrlFilter,
				'http://www.pаypal.com',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'www.xn--pypal-4ve.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'http://www.pаypal.com'
						]
					]
				],
				function ($configurator)
				{
					if (!function_exists('idn_to_ascii'))
					{
						$this->markTestSkipped('idn_to_ascii() is required.');
					}

					// This is a paypal homograph
					$configurator->urlConfig->disallowHost('pаypal.com');
				}
			],
			[
				new UrlFilter,
				'http://www.example.org',
				'http://www.example.org',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			],
			[
				new UrlFilter,
				'http://www.example.org',
				'http://www.example.org',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			],
			[
				new UrlFilter,
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
				new UrlFilter,
				'http://evil.example.com',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'evil.example.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'http://evil.example.com'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			],
			[
				new UrlFilter,
				'http://example.org.example.com',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'example.org.example.com',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'http://example.org.example.com'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			],
			[
				new UrlFilter,
				// PHP's parse_url() identifies example.com as host, browsers think it's localhost
				'http://localhost#foo@example.com/bar',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'localhost',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '#foo@example.com/bar',
							'attrValue' => 'http://localhost#foo@example.com/bar'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('localhost');
				}
			],
			[
				new UrlFilter,
				'http://@localhost',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => 'localhost',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'http://@localhost'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('localhost');
				}
			],
			[
				new UrlFilter,
				'http://user@example.org@localhost',
				false,
				[
					[
						'err',
						'URL host is not allowed',
						[
							'scheme'    => 'http',
							'user'      => 'user@example.org',
							'pass'      => '',
							'host'      => 'localhost',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'http://user@example.org@localhost'
						]
					]
				],
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('localhost');
				}
			],
			[
				new UrlFilter,
				'http:',
				false,
				[
					[
						'err',
						'Missing host',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => '',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'http:'
						]
					]
				]
			],
			[
				new UrlFilter,
				'http:?foo',
				false,
				[
					[
						'err',
						'Missing host',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => '',
							'port'      => '',
							'path'      => '',
							'query'     => '?foo',
							'fragment'  => '',
							'attrValue' => 'http:?foo'
						]
					]
				]
			],
			[
				new UrlFilter,
				'http:#foo',
				false,
				[
					[
						'err',
						'Missing host',
						[
							'scheme'    => 'http',
							'user'      => '',
							'pass'      => '',
							'host'      => '',
							'port'      => '',
							'path'      => '',
							'query'     => '',
							'fragment'  => '#foo',
							'attrValue' => 'http:#foo'
						]
					]
				]
			],
			[
				new UrlFilter,
				'file:///foo.txt',
				'file:///foo.txt',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('file');
				}
			],
			[
				new UrlFilter,
				'file://localhost/c:/WINDOWS/clock.avi',
				'file://localhost/c:/WINDOWS/clock.avi',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('file');
				}
			],
			[
				new UrlFilter,
				'http://example.org/Pok%c3%a9mon%c2%ae',
				'http://example.org/Pok%C3%A9mon%C2%AE',
			],
			[new IdentifierFilter, '123abcABC', '123abcABC'],
			[new IdentifierFilter, '-_-', '-_-'],
			[new IdentifierFilter, 'a b', false],
			[new ColorFilter, '#123abc', '#123abc'],
			[new ColorFilter, 'red', 'red'],
			[new ColorFilter, 'rgb(12,34,56)', 'rgb(12,34,56)'],
			[new ColorFilter, 'rgb(12, 34, 56)', 'rgb(12, 34, 56)'],
			[new ColorFilter, '#1234567', false],
			[new ColorFilter, 'blue()', false],
			[
				new SimpletextFilter,
				'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+.,_ ', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+.,_ '
			],
			[new SimpletextFilter, 'a()b', false],
			[new SimpletextFilter, 'a[]b', false],
			[new RegexpFilter('/^[A-Z]+$/D'), 'ABC', 'ABC'],
			[new RegexpFilter('/^[A-Z]+$/D'), 'Abc', false],
			[new EmailFilter, 'example@example.com', 'example@example.com'],
			[new EmailFilter, 'example@example.com()', false],
			[new MapFilter(['uno' => 'one', 'dos' => 'two']), 'dos', 'two'],
			[new MapFilter(['uno' => 'one', 'dos' => 'two']), 'three', 'three'],
			[new MapFilter(['uno' => 'one', 'dos' => 'two'], true, true), 'three', false],
			[new IpFilter, '8.8.8.8', '8.8.8.8'],
			[new IpFilter, 'ff02::1', 'ff02::1'],
			[new IpFilter, 'localhost', false],
			[new Ipv4Filter, '8.8.8.8', '8.8.8.8'],
			[new Ipv4Filter, 'ff02::1', false],
			[new Ipv4Filter, 'localhost', false],
			[new Ipv6Filter, '8.8.8.8', false],
			[new Ipv6Filter, 'ff02::1', 'ff02::1'],
			[new Ipv6Filter, 'localhost', false],
			[new IpportFilter, '8.8.8.8:80', '8.8.8.8:80'],
			[new IpportFilter, '[ff02::1]:80', '[ff02::1]:80'],
			[new IpportFilter, 'localhost:80', false],
			[new IpportFilter, '[localhost]:80', false],
			[new IpportFilter, '8.8.8.8', false],
			[new IpportFilter, 'ff02::1', false],
			[new HashmapFilter(['foo' => 'bar']), 'foo', 'bar'],
			[new HashmapFilter(['foo' => 'bar']), 'bar', 'bar'],
			[new HashmapFilter(['foo' => 'bar'], false), 'bar', 'bar'],
			[new HashmapFilter(['foo' => 'bar'], true), 'bar', false],
			[new FalseFilter, 'bar', false],
			[new FalseFilter, 'false', false],
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
			[
				'?foo&bar=1',
				'?foo&bar=1'
			],
			[
				'#foo',
				'#foo'
			],
			[
				'%FOO%BAR',
				'%25FOO%BAR'
			],
		];
	}
}