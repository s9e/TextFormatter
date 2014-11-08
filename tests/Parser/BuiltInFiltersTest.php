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
	public function testParseUrl($url, $expected, $setup = null)
	{
		if (isset($setup))
		{
			$setup();
		}

		$default = array(
			'scheme'   => '',
			'user'     => '',
			'pass'     => '',
			'host'     => '',
			'port'     => '',
			'path'     => '',
			'query'    => '',
			'fragment' => ''
		);

		$this->assertSame(array_merge($default, $expected), BuiltInFilters::parseUrl($url));
	}

	public function getParseUrlTests()
	{
		$_this = $this;

		return array(
			array(
				'',
				array()
			),
			array(
				// parse_url() identifies reddit.com as host, browsers think it's localhost
				'http://localhost#foo@reddit.com/bar',
				array(
					'scheme'   => 'http',
					'host'     => 'localhost',
					'fragment' => 'foo@reddit.com/bar'
				)
			),
			array(
				'http://@localhost',
				array(
					'scheme'   => 'http',
					'host'     => 'localhost'
				)
			),
			array(
				'javascript:alert(1)',
				array(
					'scheme'   => 'javascript',
					'path'     => 'alert(1)'
				)
			),
			array(
				'http://us3r@localhost',
				array(
					'scheme'   => 'http',
					'user'     => 'us3r',
					'host'     => 'localhost'
				)
			),
			array(
				'http://us3r:p4ss@localhost',
				array(
					'scheme'   => 'http',
					'user'     => 'us3r',
					'pass'     => 'p4ss',
					'host'     => 'localhost'
				)
			),
			array(
				'http://localhost:80',
				array(
					'scheme'   => 'http',
					'host'     => 'localhost',
					'port'     => '80'
				)
			),
			array(
				'http://localhost/:80',
				array(
					'scheme'   => 'http',
					'host'     => 'localhost',
					'path'     => '/:80'
				)
			),
			array(
				'http://localhost/foo?bar=1',
				array(
					'scheme'   => 'http',
					'host'     => 'localhost',
					'path'     => '/foo',
					'query'    => 'bar=1'
				)
			),
			array(
				'http://localhost/foo#?bar=1',
				array(
					'scheme'   => 'http',
					'host'     => 'localhost',
					'path'     => '/foo',
					'fragment' => '?bar=1'
				)
			),
			array(
				'http://user@example.org@localhost',
				array(
					'scheme'   => 'http',
					'host'     => 'localhost',
					'user'     => 'user@example.org'
				)
			),
			array(
				'//example.org/:foo',
				array(
					'host'     => 'example.org',
					'path'     => '/:foo'
				)
			),
			array(
				'/foo?k=1',
				array(
					'path'     => '/foo',
					'query'    => 'k=1'
				)
			),
			array(
				'foo?k=1',
				array(
					'path'     => 'foo',
					'query'    => 'k=1'
				)
			),
			array(
				'#foo',
				array(
					'fragment' => 'foo'
				)
			),
			array(
				'https://[2001:db8:85a3:8d3:1319:8a2e:370:7348]:443/',
				array(
					'scheme'   => 'https',
					'host'     => '[2001:db8:85a3:8d3:1319:8a2e:370:7348]',
					'port'     => '443',
					'path'     => '/'
				)
			),
			array(
				'http:///example.org',
				array(
					'scheme'   => 'http',
					'host'     => '',
					'path'     => '/example.org'
				)
			),
			array(
				'file:///example.org',
				array(
					'scheme'   => 'file',
					'host'     => '',
					'path'     => '/example.org'
				)
			),
			array(
				'HTTP://example.org',
				array(
					'scheme'   => 'http',
					'host'     => 'example.org'
				)
			),
			array(
				'http://^*!example.org',
				array(
					'scheme'   => 'http',
					'host'     => '^*!example.org'
				)
			),
			array(
				'http://www.älypää.com',
				array(
					'scheme'   => 'http',
					'host'     => 'www.xn--lyp-plada.com'
				),
				function () use ($_this)
				{
					if (!function_exists('idn_to_ascii'))
					{
						$_this->markTestSkipped('idn_to_ascii() is required.');
					}
				}
			),
			array(
				"http://evil\xEF\xBD\xA1example.com.\xEF\xBD\xA1./",
				array(
					'scheme'   => 'http',
					'host'     => 'evil.example.com',
					'path'     => '/'
				)
			),
			array(
				'mailto:joe@example.org',
				array(
					'scheme'   => 'mailto',
					'path'     => 'joe@example.org'
				)
			),
			array(
				'0',
				array(
					'path'     => '0'
				)
			),
		);
	}

	/**
	* @testdox Filters work
	* @dataProvider getData
	*/
	public function testFilters($filter, $original, $expected, $logs = array(), $setup = null)
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
			array(
				'attrName'       => 'foo',
				'attrValue'      => $original,
				'logger'         => $logger,
				'registeredVars' => $config['registeredVars']
			)
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
		$urlConfig = array(
			'allowedSchemes'  => '/^https?$/',
			'disallowedHosts' => '/evil/'
		);

		$urls = array(
			'http://example.org' => 'http://example.org',
			'http://evil.org'    => false,
			'hax://example.org'  => false
		);

		foreach ($urls as $original => $expected)
		{
			$this->assertSame($expected, BuiltInFilters::filterUrl($original, $urlConfig));
		}
	}

	public function getData()
	{
		$_this = $this;

		return array(
			array(new Alnum, '', false),
			array(new Alnum, 'abcDEF', 'abcDEF'),
			array(new Alnum, 'abc_def', false),
			array(new Alnum, '0123', '0123'),
			array(new Alnum, 'é', false),
			array(new Range(2, 5), '2', 2),
			array(new Range(2, 5), '5', 5),
			array(new Range(-5, 5), '-5', -5),
			array(
				new Range(2, 5),
				'1',
				2,
				array(
					array(
						'warn',
						'Value outside of range, adjusted up to min value',
						array('attrValue' => 1, 'min' => 2, 'max' => 5)
					)
				)
			),
			array(
				new Range(2, 5),
				'10',
				5,
				array(
					array(
						'warn',
						'Value outside of range, adjusted down to max value',
						array('attrValue' => 10, 'min' => 2, 'max' => 5)
					)
				)
			),
			array(new Range(2, 5), '5x', false),
			array(
				new Url,
				'http://www.älypää.com',
				'http://www.xn--lyp-plada.com',
				array(),
				function () use ($_this)
				{
					if (!function_exists('idn_to_ascii'))
					{
						$_this->markTestSkipped('idn_to_ascii() is required.');
					}
				}
			),
			array(
				new Url,
				'http://en.wikipedia.org/wiki/Matti_Nykänen', 'http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen'
			),
			array(
				new Url,
				'https://[2001:db8:85a3:8d3:1319:8a2e:370:7348]:443/', 'https://[2001:db8:85a3:8d3:1319:8a2e:370:7348]:443/'
			),
			array(
				new Url,
				'http://127.0.0.1:80/',
				'http://127.0.0.1:80/'
			),
			array(new Url, '//foo', '//foo'),
			array(new Url, '/foo', '/foo'),
			array(new Url, '?foo', '?foo'),
			array(new Url, '#bar', '#bar'),
			array(new Url, '://bar', '%3A//bar'),
			array(new Url, '*://bar', '*%3A//bar'),
			array(new Url, '/:foo/:bar', '/:foo/:bar'),
			array(
				new Url,
				'http://user:pass@en.wikipedia.org:80/wiki/Matti_Nykänen?foo&bar#baz', 'http://user:pass@en.wikipedia.org:80/wiki/Matti_Nyk%C3%A4nen?foo&bar#baz'
			),
			array(
				new Url,
				'http://älypää.com:älypää.com@älypää.com',
				'http://%C3%A4lyp%C3%A4%C3%A4.com:%C3%A4lyp%C3%A4%C3%A4.com@xn--lyp-plada.com',
				array(),
				function () use ($_this)
				{
					if (!function_exists('idn_to_ascii'))
					{
						$_this->markTestSkipped('idn_to_ascii() is required.');
					}
				}
			),
			array(
				new Url,
				'javascript:alert()',
				false,
				array(
					array(
						'err',
						'URL scheme is not allowed',
						array('attrValue' => 'javascript:alert()', 'scheme' => 'javascript')
					)
				)
			),
			array(new Url, 'http://www.example.com', 'http://www.example.com'),
			array(new Url, '//www.example.com', '//www.example.com'),
			array(new Url, 'HTTP://www.example.com', 'http://www.example.com'),
			array(new Url, ' http://www.example.com ', 'http://www.example.com'),
			array(new Url, "http://example.com/''", 'http://example.com/%27%27'),
			array(new Url, 'http://example.com/""', 'http://example.com/%22%22'),
			array(new Url, 'http://example.com/(', 'http://example.com/%28'),
			array(new Url, 'http://example.com/)', 'http://example.com/%29'),
			array(new Url, "http://example.com/x\0y", 'http://example.com/x%00y'),
			array(new Url, "http://example.com/x y", 'http://example.com/x%20y'),
			array(new Url, 'http://example.com/foo.php?a[]=1', 'http://example.com/foo.php?a%5B%5D=1'),
			array(new Url, 'http://example.com/</script>', 'http://example.com/%3C/script%3E'),
			array(
				new Url,
				"http://example.com/\xE2\x80\xA8",
				'http://example.com/%E2%80%A8'
			),
			array(
				new Url,
				"http://example.com/\xE2\x80\xA9",
				'http://example.com/%E2%80%A9'
			),
			array(
				new Url,
				'ftp://example.com',
				false,
				array(
					array(
						'err',
						'URL scheme is not allowed',
						array(
							'attrValue' => 'ftp://example.com',
							'scheme'    => 'ftp'
						)
					)
				)
			),
			array(
				new Url,
				'ftp://example.com',
				'ftp://example.com',
				array(),
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('ftp');
				}
			),
			array(
				new Url,
				'http://evil.example.com',
				false,
				array(
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => 'http://evil.example.com',
							'host'      => 'evil.example.com'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			),
			array(
				new Url,
				'//evil.example.com',
				false,
				array(
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => '//evil.example.com',
							'host'      => 'evil.example.com'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			),
			array(
				new Url,
				"http://evil\xE3\x80\x82example.com",
				false,
				array(
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => "http://evil\xE3\x80\x82example.com",
							'host'      => 'evil.example.com'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			),
			array(
				new Url,
				"http://evil\xEF\xBC\x8Eexample.com",
				false,
				array(
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => "http://evil\xEF\xBC\x8Eexample.com",
							'host'      => 'evil.example.com'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			),
			array(
				new Url,
				"http://evil\xEF\xBD\xA1example.com",
				false,
				array(
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => "http://evil\xEF\xBD\xA1example.com",
							'host'      => 'evil.example.com'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			),
			array(
				new Url,
				"http://evil.example.com.",
				false,
				array(
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => "http://evil.example.com.",
							'host'      => 'evil.example.com'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			),
			array(
				new Url,
				"http://evil\xEF\xBD\xA1example.com\xEF\xBD\xA1",
				false,
				array(
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => "http://evil\xEF\xBD\xA1example.com\xEF\xBD\xA1",
							'host'      => 'evil.example.com'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			),
			array(
				new Url,
				"http://evil.ex%41mple.com",
				false,
				array(
					array(
						'err',
						'URL host is invalid',
						array(
							'attrValue' => 'http://evil.ex%41mple.com',
							'host'      => 'evil.ex%41mple.com'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.example.com');
				}
			),
			array(
				new Url,
				'http://www.pаypal.com',
				false,
				array(
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => 'http://www.pаypal.com',
							'host'      => 'www.xn--pypal-4ve.com'
						)
					)
				),
				function ($configurator) use ($_this)
				{
					if (!function_exists('idn_to_ascii'))
					{
						$_this->markTestSkipped('idn_to_ascii() is required.');
					}

					// This is a paypal homograph
					$configurator->urlConfig->disallowHost('pаypal.com');
				}
			),
			array(
				new Url,
				'http://www.example.org',
				'http://www.example.org',
				array(),
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			),
			array(
				new Url,
				'http://www.example.org',
				'http://www.example.org',
				array(),
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			),
			array(
				new Url,
				'http://www.example.org',
				'http://www.example.org',
				array(),
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
					$configurator->urlConfig->restrictHost('example.com');
				}
			),
			array(
				new Url,
				'http://evil.example.com',
				false,
				array(
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => 'http://evil.example.com',
							'host'      => 'evil.example.com'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			),
			array(
				new Url,
				'http://example.org.example.com',
				false,
				array(
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => 'http://example.org.example.com',
							'host'      => 'example.org.example.com'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->restrictHost('example.org');
				}
			),
			array(new Url, 'http:', false),
			array(new Url, 'http:?foo', false),
			array(new Url, 'http:#foo', false),
			array(
				new Url,
				'file:///foo.txt',
				'file:///foo.txt',
				array(),
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('file');
				}
			),
			array(
				new Url,
				'file://localhost/c:/WINDOWS/clock.avi',
				'file://localhost/c:/WINDOWS/clock.avi',
				array(),
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('file');
				}
			),
			array(
				new Url,
				'http://example.org/Pok%c3%a9mon%c2%ae',
				'http://example.org/Pok%C3%A9mon%C2%AE',
			),
			array(new Identifier, '123abcABC', '123abcABC'),
			array(new Identifier, '-_-', '-_-'),
			array(new Identifier, 'a b', false),
			array(new Color, '#123abc', '#123abc'),
			array(new Color, 'red', 'red'),
			array(new Color, 'rgb(12,34,56)', 'rgb(12,34,56)'),
			array(new Color, 'rgb(12, 34, 56)', 'rgb(12, 34, 56)'),
			array(new Color, '#1234567', false),
			array(new Color, 'blue()', false),
			array(
				new Simpletext,
				'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+.,_ ', 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-+.,_ '
			),
			array(new Simpletext, 'a()b', false),
			array(new Simpletext, 'a[]b', false),
			array(new Regexp('/^[A-Z]+$/D'), 'ABC', 'ABC'),
			array(new Regexp('/^[A-Z]+$/D'), 'Abc', false),
			array(new Email, 'example@example.com', 'example@example.com'),
			array(new Email, 'example@example.com()', false),
			array(new Map(array('uno' => 'one', 'dos' => 'two')), 'dos', 'two'),
			array(new Map(array('uno' => 'one', 'dos' => 'two')), 'three', 'three'),
			array(new Map(array('uno' => 'one', 'dos' => 'two'), true, true), 'three', false),
			array(new Ip, '8.8.8.8', '8.8.8.8'),
			array(new Ip, 'ff02::1', 'ff02::1'),
			array(new Ip, 'localhost', false),
			array(new Ipv4, '8.8.8.8', '8.8.8.8'),
			array(new Ipv4, 'ff02::1', false),
			array(new Ipv4, 'localhost', false),
			array(new Ipv6, '8.8.8.8', false),
			array(new Ipv6, 'ff02::1', 'ff02::1'),
			array(new Ipv6, 'localhost', false),
			array(new Ipport, '8.8.8.8:80', '8.8.8.8:80'),
			array(new Ipport, '[ff02::1]:80', '[ff02::1]:80'),
			array(new Ipport, 'localhost:80', false),
			array(new Ipport, '[localhost]:80', false),
			array(new Ipport, '8.8.8.8', false),
			array(new Ipport, 'ff02::1', false),
			array(new Hashmap(array('foo' => 'bar')), 'foo', 'bar'),
			array(new Hashmap(array('foo' => 'bar')), 'bar', 'bar'),
			array(new Hashmap(array('foo' => 'bar'), false), 'bar', 'bar'),
			array(new Hashmap(array('foo' => 'bar'), true), 'bar', false),
		);
	}

	/**
	* NOTE: this test is not normative. Some cases exist solely to track regressions or changes in
	*       behaviour in ext/filter
	*/
	public function getRegressionsData()
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
		return array(
			array(
				"http://example.com/''",
				'http://example.com/%27%27'
			),
			array(
				'http://example.com/""',
				'http://example.com/%22%22'
			),
			array(
				'http://example.com/((',
				'http://example.com/%28%28'
			),
			array(
				'http://example.com/))',
				'http://example.com/%29%29'
			),
			array(
				"http://example.com/x\0y",
				'http://example.com/x%00y'
			),
			array(
				'http://example.com/x y',
				'http://example.com/x%20y'
			),
			array(
				"http://example.com/x\ry",
				'http://example.com/x%0Dy'
			),
			array(
				"http://example.com/x\ny",
				'http://example.com/x%0Ay'
			),
			array(
				'http://example.com/foo.php?a[]=1',
				'http://example.com/foo.php?a%5B%5D=1'
			),
			array(
				'http://example.com/</script>',
				'http://example.com/%3C/script%3E'
			),
			array(
				"http://example.com/\xE2\x80\xA8",
				'http://example.com/%E2%80%A8',
			),
			array(
				"http://example.com/\xE2\x80\xA8",
				'http://example.com/%E2%80%A8',
			),
			array(
				"http://example.com/\xE2\x80\xA9",
				'http://example.com/%E2%80%A9',
			),
			array(
				"http://example.com/♥",
				'http://example.com/%E2%99%A5',
			),
			array(
				'?foo&bar=1',
				'?foo&bar=1'
			),
			array(
				'#foo',
				'#foo'
			),
			array(
				'%FOO%BAR',
				'%25FOO%BAR'
			),
		);
	}
}