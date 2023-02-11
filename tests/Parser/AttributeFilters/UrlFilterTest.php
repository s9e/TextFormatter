<?php

namespace s9e\TextFormatter\Tests\Parser\AttributeFilters;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
use s9e\TextFormatter\Parser\AttributeFilters\UrlFilter as FilterClass;

/**
* @covers s9e\TextFormatter\Parser\AttributeFilters\UrlFilter
*/
class UrlFilterTest extends AbstractFilterTestClass
{
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
			$this->assertSame($expected, FilterClass::filter($original, $urlConfig));
		}
	}

	/**
	* @testdox sanitizeUrl() tests
	* @dataProvider getSanitizeUrlTests
	*/
	public function testSanitizeUrl($url, $expected)
	{
		$this->assertSame($expected, FilterClass::sanitizeUrl($url));
	}

	public static function getSanitizeUrlTests()
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

	public static function getFilterTests()
	{
		return [
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
			[new UrlFilter, "http://example.com/x y", 'http://example.com/x%20y'],
			[new UrlFilter, 'http://example.com/foo.php?a[]=1', 'http://example.com/foo.php?a%5B%5D=1'],
			[new UrlFilter, 'http://example.com/</script>', 'http://example.com/%3C/script%3E'],
			[
				new UrlFilter,
				"http://example.com/\xE2\x80\xA8x",
				'http://example.com/%E2%80%A8x'
			],
			[
				new UrlFilter,
				"http://example.com/\xE2\x80\xA9x",
				'http://example.com/%E2%80%A9x'
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
				'http:///example.org',
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
							'path'      => '/example.org',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'http:///example.org'
						]
					]
				]
			],
			[
				new UrlFilter,
				'http:example.org',
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
							'path'      => 'example.org',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'http:example.org'
						]
					]
				]
			],
			[
				new UrlFilter,
				'HTTP:EXAMPLE.ORG',
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
							'path'      => 'EXAMPLE.ORG',
							'query'     => '',
							'fragment'  => '',
							'attrValue' => 'HTTP:EXAMPLE.ORG'
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
			[
				new UrlFilter,
				'mailto:example@example.org',
				'mailto:example@example.org',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('mailto');
				}
			],
			[
				new UrlFilter,
				'mailto:example@example.org?subject=(sub)&bcc=example2@example.org',
				'mailto:example@example.org?subject=%28sub%29&bcc=example2@example.org',
				[],
				function ($configurator)
				{
					$configurator->urlConfig->allowScheme('mailto');
				}
			],
			[
				new UrlFilter,
				'http://example.org/ !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\]^_`abcdefghijklmnopqrstuvwxyz{|}~',
				'http://example.org/%20!%22#$%25&%27%28%29*+,-./0123456789:;%3C=%3E?@ABCDEFGHIJKLMNOPQRSTUVWXYZ%5B%5C%5D%5E_%60abcdefghijklmnopqrstuvwxyz%7B%7C%7D~'
			],
		];
	}
}