<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Alnum;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Choice;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Color;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Email;
use s9e\TextFormatter\Configurator\Items\AttributeFilters\Float;
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
use s9e\TextFormatter\Parser\FilterProcessing;
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
	public function testFilters($filter, $original, $expected, array $logs = array(), $setup = null)
	{
		$logger = new Logger;

		$this->assertSame(
			$expected,
			Hax::filterValue($original, $filter, $logger, $setup)
		);

		$this->assertSame($logs, $logger->get(), "Logs don't match");
	}

	public function getData()
	{
		return array(
			array(new Alnum, '', false),
			array(new Alnum, 'abcDEF', 'abcDEF'),
			array(new Alnum, 'abc_def', false),
			array(new Alnum, '0123', '0123'),
			array(new Alnum, 'é', false),
			array(new Range(2, 5), '2', 2),
			array(new Range(2, 5), '5', 5),
			array(new Range(-5, 5), '-5', -5),
			array(new Range(2, 5), '1', 2, array(
				array('warn', 'Value outside of range, adjusted up to min value', array(
					'attrValue' => 1, 'min' => 2, 'max' => 5
				))
			)),
			array(new Range(2, 5), '10', 5, array(
				array('warn', 'Value outside of range, adjusted down to max value', array(
					'attrValue' => 10, 'min' => 2, 'max' => 5
				))
			)),
			array(new Range(2, 5), '5x', false),
			array(new Url, 'http://www.älypää.com', 'http://www.xn--lyp-plada.com'),
			array(
				new Url,
				'http://en.wikipedia.org/wiki/Matti_Nykänen', 'http://en.wikipedia.org/wiki/Matti_Nyk%C3%A4nen'
			),
			array(
				new Url,
				'http://user:pass@en.wikipedia.org:80/wiki/Matti_Nykänen?foo&bar#baz', 'http://user:pass@en.wikipedia.org:80/wiki/Matti_Nyk%C3%A4nen?foo&bar#baz'
			),
			array(
				new Url,
				'http://älypää.com:älypää.com@älypää.com',
				'http://%C3%A4lyp%C3%A4%C3%A4.com:%C3%A4lyp%C3%A4%C3%A4.com@xn--lyp-plada.com'
			),
			array(new Url, 'javascript:alert()', false),
			array(new Url, 'http://www.example.com', 'http://www.example.com'),
			array(new Url, '//www.example.com', '//www.example.com'),
			array(
				new Url,
				'//www.example.com',
				false,
				array(),
				function ($configurator)
				{
					$configurator->urlConfig->requireScheme();
				}
			),
			array(new Url, 'HTTP://www.example.com', 'http://www.example.com'),
			array(new Url, ' http://www.example.com ', 'http://www.example.com'),
			array(new Url, "http://example.com/''", 'http://example.com/%27%27'),
			array(new Url, 'http://example.com/""', 'http://example.com/%22%22'),
			array(new Url, 'http://example.com/(', 'http://example.com/%28'),
			array(new Url, 'http://example.com/)', 'http://example.com/%29'),
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
					// This is a paypal homograph
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
							'attrValue' => 'http://www.xn--pypal-4ve.com',
							'host'      => 'www.xn--pypal-4ve.com'
						)
					)
				),
				function ($configurator)
				{
					// This is a paypal homograph
					$configurator->urlConfig->disallowHost('pаypal.com');
				}
			),
			array(
				new Url,
				'http://t.co/gksG6xlq',
				'http://twitter.com/',
				array(
					array(
						'debug',
						'Resolved redirect',
						array(
							'from' => 'http://t.co/gksG6xlq',
							'to'   => 'http://twitter.com/'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->resolveRedirectsFrom('t.co');
					Hax::fakeRedirect('http://t.co/gksG6xlq', 'http://twitter.com/');
				}
			),
			array(
				new Url,
				'http://bit.ly/go',
				'http://bit.ly/',
				array(
					array(
						'debug',
						'Resolved redirect',
						array(
							'from' => 'http://bit.ly/go',
							'to'   => 'http://bit.ly/2lkCBm'
						)
					),
					array(
						'debug',
						'Resolved redirect',
						array(
							'from' => 'http://bit.ly/2lkCBm',
							'to'   => 'http://bit.ly/'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->resolveRedirectsFrom('bit.ly');
					Hax::fakeRedirect('http://bit.ly/go',     'http://bit.ly/2lkCBm');
					Hax::fakeRedirect('http://bit.ly/2lkCBm', 'http://bit.ly/');
				}
			),
			array(
				new Url,
				'http://bit.ly/2lkCBm',
				false,
				array(
					array(
						'err',
						'Could not resolve redirect',
						array(
							'attrValue' => 'http://bit.ly/2lkCBm'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->resolveRedirectsFrom('bit.ly');
					Hax::fakeRedirect('http://bit.ly/2lkCBm', false);
				}
			),
			array(
				new Url,
				'http://bit.ly/2lkCBm',
				false,
				array(
					array(
						'debug',
						'Resolved redirect',
						array(
							'from' => 'http://bit.ly/2lkCBm',
							'to'   => 'http://bit.ly/2lkCBm'
						)
					),
					array(
						'err',
						'Infinite recursion detected while following redirects',
						array(
							'attrValue' => 'http://bit.ly/2lkCBm'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->resolveRedirectsFrom('bit.ly');
					Hax::fakeRedirect('http://bit.ly/2lkCBm', 'http://bit.ly/2lkCBm');
				}
			),
			array(
				new Url,
				'http://t.co/foo',
				false,
				array(
					array(
						'debug',
						'Resolved redirect',
						array(
							'from' => 'http://t.co/foo',
							'to'   => 'http://t.co/bar'
						)
					),
					array(
						'debug',
						'Resolved redirect',
						array(
							'from' => 'http://t.co/bar',
							'to'   => 'http://t.co/baz'
						)
					),
					array(
						'debug',
						'Resolved redirect',
						array(
							'from' => 'http://t.co/baz',
							'to'   => 'http://t.co/foo'
						)
					),
					array(
						'err',
						'Infinite recursion detected while following redirects',
						array(
							'attrValue' => 'http://t.co/foo'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->resolveRedirectsFrom('t.co');
					Hax::fakeRedirect('http://t.co/foo', 'http://t.co/bar');
					Hax::fakeRedirect('http://t.co/bar', 'http://t.co/baz');
					Hax::fakeRedirect('http://t.co/baz', 'http://t.co/foo');
				}
			),
			array(
				new Url,
				'http://redirect.tld',
				false,
				array(
					array(
						'debug',
						'Resolved redirect',
						array(
							'from' => 'http://redirect.tld',
							'to'   => 'http://evil.tld'
						)
					),
					array(
						'err',
						'URL host is not allowed',
						array(
							'attrValue' => 'http://evil.tld',
							'host'      => 'evil.tld'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->disallowHost('evil.tld');
					$configurator->urlConfig->resolveRedirectsFrom('redirect.tld');
					Hax::fakeRedirect('http://redirect.tld', 'http://evil.tld');
				}
			),
			array(
				new Url,
				'//t.co/gksG6xlq',
				'http://twitter.com/',
				array(
					array(
						'debug',
						'Resolved redirect',
						array(
							'from' => 'http://t.co/gksG6xlq',
							'to'   => 'http://twitter.com/'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->resolveRedirectsFrom('t.co');
					$configurator->urlConfig->setDefaultScheme('http');
					Hax::fakeRedirect('http://t.co/gksG6xlq', 'http://twitter.com/');
				}
			),
			array(
				new Url,
				'http://js.tld',
				false,
				array(
					array(
						'debug',
						'Resolved redirect',
						array(
							'from' => 'http://js.tld',
							'to'   => 'javascript:alert'
						)
					)
				),
				function ($configurator)
				{
					$configurator->urlConfig->resolveRedirectsFrom('js.tld');
					Hax::fakeRedirect('http://js.tld', 'javascript:alert');
				}
			),
			array(new Identifier, '123abcABC', '123abcABC'),
			array(new Identifier, '-_-', '-_-'),
			array(new Identifier, 'a b', false),
			array(new Color, '#123abc', '#123abc'),
			array(new Color, 'red', 'red'),
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
}

class Hax
{
	use FilterProcessing;

	static protected $redirectTo = array();

	public static function filterValue($attrValue, AttributeFilter $filter, Logger $logger, $setup = null)
	{
		$configurator = new Configurator;
		$configurator
			->tags->add('FOO')
			->attributes->add('foo')
			->filterChain->append($filter);

		if (isset($setup))
		{
			$setup($configurator);
		}

		$config = $configurator->asConfig();
		ConfigHelper::filterVariants($config);

		if (self::$redirectTo)
		{
			stream_wrapper_unregister('http');
			stream_wrapper_register('http', __CLASS__);
		}

		try
		{
			$attrValue = self::executeFilter(
				$config['tags']['FOO']['attributes']['foo']['filterChain'][0],
				array(
					'attrName'       => 'foo',
					'attrValue'      => $attrValue,
					'logger'         => $logger,
					'registeredVars' => $config['registeredVars']
				)
			);
		}
		catch (Exception $e)
		{
		}

		if (self::$redirectTo)
		{
			self::$redirectTo = array();
			stream_wrapper_restore('http');
		}

		if (isset($e))
		{
			throw $e;
		}

		return $attrValue;
	}

	public static function fakeRedirect($from, $to)
	{
		self::$redirectTo[$from] = $to;
	}

	public function stream_open($url)
	{
		if (isset(self::$redirectTo[$url]))
		{
			if (self::$redirectTo[$url] === false)
			{
				return false;
			}

			$this->{'0'} = 'Location: ' . self::$redirectTo[$url];
		}

		return true;
	}

	public function stream_stat()
	{
		return false;
	}

	public function stream_read()
	{
		return '';
	}

	public function stream_eof()
	{
		return true;
	}
}