<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\UrlConfig;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\UrlConfig
*/
class UrlConfigTest extends Test
{
	protected UrlConfig $urlConfig;

	protected function setUp(): void
	{
		parent::setUp();
		$this->urlConfig = new UrlConfig;
	}

	/**
	* @testdox asConfig() returns a Regexp instance for disallowedHosts
	*/
	public function testAsConfigDisallowedHostsRegexp()
	{
		$this->urlConfig->disallowHost('pаypal.com');
		$urlConfig = $this->urlConfig->asConfig();

		$this->assertArrayHasKey('disallowedHosts', $urlConfig);
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Regexp',
			$urlConfig['disallowedHosts']
		);
	}

	/**
	* @requires function idn_to_ascii
	* @testdox Disallowed IDNs are punycoded
	*/
	public function testDisallowedIDNsArePunycoded()
	{
		$this->urlConfig->disallowHost('pаypal.com');
		$urlConfig = $this->urlConfig->asConfig();

		$this->assertArrayHasKey('disallowedHosts', $urlConfig);
		$this->assertStringContainsString('xn--pypal-4ve\\.com', (string) $urlConfig['disallowedHosts']);
	}

	/**
	* @testdox disallowHost('example.org') disallows "example.org"
	*/
	public function testDisallowHost()
	{
		$this->urlConfig->disallowHost('example.org');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertMatchesRegularExpression((string) $urlConfig['disallowedHosts'], 'example.org');
	}

	/**
	* @testdox disallowHost('example.org') disallows "EXAMPLE.ORG"
	*/
	public function testDisallowHostCaseInsensitive()
	{
		$this->urlConfig->disallowHost('example.org');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertMatchesRegularExpression((string) $urlConfig['disallowedHosts'], 'EXAMPLE.ORG');
	}

	/**
	* @testdox disallowHost('example.org') disallows "www.example.org"
	*/
	public function testDisallowHostSubdomains()
	{
		$this->urlConfig->disallowHost('example.org');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertMatchesRegularExpression((string) $urlConfig['disallowedHosts'], 'www.example.org');
	}

	/**
	* @testdox disallowHost('example.org') does not disallow "myexample.org"
	*/
	public function testDisallowHostSubdomainsNoPartialMatch()
	{
		$this->urlConfig->disallowHost('example.org');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertDoesNotMatchRegularExpression((string) $urlConfig['disallowedHosts'], 'myexample.org');
	}

	/**
	* @testdox disallowHost('example.org', false) does not disallow "www.example.org"
	*/
	public function testDisallowHostNoSubdomains()
	{
		$this->urlConfig->disallowHost('example.org', false);
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertDoesNotMatchRegularExpression((string) $urlConfig['disallowedHosts'], 'www.example.org');
	}

	/**
	* @testdox disallowHost('*.example.org') disallows "www.example.org"
	*/
	public function testDisallowHostWithWildcard()
	{
		$this->urlConfig->disallowHost('*.example.org');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertMatchesRegularExpression((string) $urlConfig['disallowedHosts'], 'www.example.org');
	}

	/**
	* @testdox disallowHost('*.example.org') disallows "www.xxx.example.org"
	*/
	public function testDisallowHostWithWildcard2()
	{
		$this->urlConfig->disallowHost('*.example.org');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertMatchesRegularExpression((string) $urlConfig['disallowedHosts'], 'www.xxx.example.org');
	}

	/**
	* @testdox disallowHost('*.example.org') does not disallow "example.org"
	*/
	public function testDisallowHostWithWildcard3()
	{
		$this->urlConfig->disallowHost('*.example.org');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertDoesNotMatchRegularExpression((string) $urlConfig['disallowedHosts'], 'example.org');
	}

	/**
	* @testdox disallowHost('*.example.org') does not disallow "example.org.org"
	*/
	public function testDisallowHostWithWildcard4()
	{
		$this->urlConfig->disallowHost('*.example.org');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertDoesNotMatchRegularExpression((string) $urlConfig['disallowedHosts'], 'example.org.org');
	}

	/**
	* @testdox disallowHost('*xxx*') disallows "xxx.com"
	*/
	public function testDisallowHostWithWildcard5()
	{
		$this->urlConfig->disallowHost('*xxx*');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertMatchesRegularExpression((string) $urlConfig['disallowedHosts'], 'xxx.com');
	}

	/**
	* @testdox disallowHost('*xxx*') disallows "foo.xxx"
	*/
	public function testDisallowHostWithWildcard6()
	{
		$this->urlConfig->disallowHost('*xxx*');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertMatchesRegularExpression((string) $urlConfig['disallowedHosts'], 'foo.xxx');
	}

	/**
	* @testdox disallowHost('*xxx*') disallows "myxxxsite.com"
	*/
	public function testDisallowHostWithWildcard7()
	{
		$this->urlConfig->disallowHost('*xxx*');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertMatchesRegularExpression((string) $urlConfig['disallowedHosts'], 'myxxxsite.com');
	}

	/**
	* @testdox restrictHost('example.org') allows "www.example.org"
	*/
	public function testRestrictHostSubdomains()
	{
		$this->urlConfig->restrictHost('example.org');
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertMatchesRegularExpression((string) $urlConfig['restrictedHosts'], 'www.example.org');
	}

	/**
	* @testdox restrictHost('example.org', false) does not allow "www.example.org"
	*/
	public function testRestrictHostNoSubdomains()
	{
		$this->urlConfig->restrictHost('example.org', false);
		$urlConfig = $this->urlConfig->asConfig();
		$this->assertDoesNotMatchRegularExpression((string) $urlConfig['restrictedHosts'], 'www.example.org');
	}

	/**
	* @testdox "http" is an allowed scheme by default
	*/
	public function testAllowSchemeHTTP()
	{
		$urlConfig = $this->urlConfig->asConfig();
		$regexp    = (string) $urlConfig['allowedSchemes'];

		$this->assertArrayHasKey('allowedSchemes', $urlConfig);
		$this->assertMatchesRegularExpression($regexp, 'http');
	}

	/**
	* @testdox "https" is an allowed scheme by default
	*/
	public function testAllowSchemeHTTPS()
	{
		$urlConfig = $this->urlConfig->asConfig();
		$regexp    = (string) $urlConfig['allowedSchemes'];

		$this->assertArrayHasKey('allowedSchemes', $urlConfig);
		$this->assertMatchesRegularExpression($regexp, 'https');
	}

	/**
	* @testdox "HTTPS" is an allowed scheme by default
	*/
	public function testAllowSchemeHTTPSCaseInsensitive()
	{
		$urlConfig = $this->urlConfig->asConfig();
		$regexp    = (string) $urlConfig['allowedSchemes'];

		$this->assertArrayHasKey('allowedSchemes', $urlConfig);
		$this->assertMatchesRegularExpression($regexp, 'HTTPS');
	}

	/**
	* @testdox "ftp" is not an allowed scheme by default
	*/
	public function testDisallowedSchemeFTP()
	{
		$urlConfig = $this->urlConfig->asConfig();
		$regexp    = (string) $urlConfig['allowedSchemes'];

		$this->assertArrayHasKey('allowedSchemes', $urlConfig);
		$this->assertDoesNotMatchRegularExpression($regexp, 'ftp');
	}

	/**
	* @testdox getAllowedSchemes() returns an array containing all the allowed schemes
	*/
	public function testGetAllowedSchemes()
	{
		$this->assertEquals(
			['http', 'https'],
			$this->urlConfig->getAllowedSchemes()
		);
	}

	/**
	* @testdox disallowScheme() removes a scheme from the list of allowed schemes
	*/
	public function testDisallowScheme()
	{
		$this->urlConfig->allowScheme('http');
		$this->urlConfig->disallowScheme('http');

		$this->assertEquals(
			['https'],
			$this->urlConfig->getAllowedSchemes()
		);
	}

	/**
	* @testdox allowScheme('ftp') allows "ftp" as scheme
	*/
	public function testAllowSchemeFTP()
	{
		$this->urlConfig->allowScheme('ftp');
		$urlConfig = $this->urlConfig->asConfig();
		$regexp    = (string) $urlConfig['allowedSchemes'];

		$this->assertArrayHasKey('allowedSchemes', $urlConfig);
		$this->assertMatchesRegularExpression($regexp, 'ftp');
	}

	/**
	* @testdox allowScheme('<invalid>') throws an exception
	*/
	public function testInvalidAllowScheme()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid scheme name '<invalid>'");

		$this->urlConfig->allowScheme('<invalid>');
	}

	/**
	* @testdox allowScheme('javascript') throws an exception
	*/
	public function testAllowSchemeJavaScript()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('The JavaScript URL scheme cannot be allowed');

		$this->urlConfig->allowScheme('javascript');
	}

	/**
	* @testdox allowScheme('javaScript') throws an exception
	*/
	public function testAllowSchemeJavaScriptCase()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('The JavaScript URL scheme cannot be allowed');

		$this->urlConfig->allowScheme('javaScript');
	}
}