<?php

namespace s9e\TextFormatter\Tests\Generator;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Generator\UrlConfig;

/**
* @covers s9e\TextFormatter\Generator\UrlConfig
*/
class UrlConfigTest extends Test
{
	public function setUp()
	{
		$this->urlConfig = new UrlConfig;
	}

	/**
	* @testdox Disallowed IDNs are punycoded
	*/
	public function Disallowed_IDNs_are_punycoded()
	{
		$this->urlConfig->disallowHost('pаypal.com');
		$urlConfig = $this->urlConfig->toConfig();

		$this->assertArrayHasKey('disallowedHosts', $urlConfig);
		$this->assertContains('xn--pypal-4ve\\.com', $urlConfig['disallowedHosts']);
	}

	/**
	* @testdox disallowHost('example.org') will disallow "example.org"
	*/
	public function testCanDisallowHosts()
	{
		$this->urlConfig->disallowHost('example.org');
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertRegexp($urlConfig['disallowedHosts'], 'example.org');
	}

	/**
	* @testdox disallowHost('example.org') will disallow "EXAMPLE.ORG"
	*/
	public function testCanDisallowHostsCaseInsensitive()
	{
		$this->urlConfig->disallowHost('example.org');
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertRegexp($urlConfig['disallowedHosts'], 'EXAMPLE.ORG');
	}

	/**
	* @testdox disallowHost('*.example.org') will disallow "www.example.org"
	*/
	public function testCanDisallowHostsWithWildcard()
	{
		$this->urlConfig->disallowHost('*.example.org');
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertRegexp($urlConfig['disallowedHosts'], 'www.example.org');
	}

	/**
	* @testdox disallowHost('*.example.org') will disallow "www.xxx.example.org"
	*/
	public function testCanDisallowHostsWithWildcard2()
	{
		$this->urlConfig->disallowHost('*.example.org');
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertRegexp($urlConfig['disallowedHosts'], 'www.xxx.example.org');
	}

	/**
	* @testdox disallowHost('*.example.org') will not disallow "example.org"
	*/
	public function testCanDisallowHostsWithWildcard3()
	{
		$this->urlConfig->disallowHost('*.example.org');
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertNotRegexp($urlConfig['disallowedHosts'], 'example.org');
	}

	/**
	* @testdox disallowHost('*.example.org') will not disallow "example.org.org"
	*/
	public function testCanDisallowHostsWithWildcard4()
	{
		$this->urlConfig->disallowHost('*.example.org');
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertNotRegexp($urlConfig['disallowedHosts'], 'example.org.org');
	}

	/**
	* @testdox disallowHost('*xxx*') will disallow "xxx.com"
	*/
	public function testCanDisallowHostsWithWildcard5()
	{
		$this->urlConfig->disallowHost('*xxx*');
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertRegexp($urlConfig['disallowedHosts'], 'xxx.com');
	}

	/**
	* @testdox disallowHost('*xxx*') will disallow "foo.xxx"
	*/
	public function testCanDisallowHostsWithWildcard6()
	{
		$this->urlConfig->disallowHost('*xxx*');
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertRegexp($urlConfig['disallowedHosts'], 'foo.xxx');
	}

	/**
	* @testdox disallowHost('*xxx*') will disallow "myxxxsite.com"
	*/
	public function testCanDisallowHostsWithWildcard7()
	{
		$this->urlConfig->disallowHost('*xxx*');
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertRegexp($urlConfig['disallowedHosts'], 'myxxxsite.com');
	}

	/**
	* @testdox resolveRedirectsFrom('bit.ly') will match "bit.ly"
	*/
	public function Url_filter_can_be_configured_to_resolve_redirects_from_a_given_host()
	{
		$this->urlConfig->resolveRedirectsFrom('bit.ly');
		$urlConfig = $this->urlConfig->toConfig();

		$this->assertArrayHasKey('resolveRedirectsHosts', $urlConfig);
		$this->assertRegexp($urlConfig['resolveRedirectsHosts'], 'bit.ly');
	}

	/**
	* @testdox "http" is an allowed scheme by default
	*/
	public function testAllowSchemeHTTP()
	{
		$urlConfig = $this->urlConfig->toConfig();

		$this->assertArrayHasKey('allowedSchemes', $urlConfig);
		$this->assertRegexp($urlConfig['allowedSchemes'], 'http');
	}

	/**
	* @testdox "https" is an allowed scheme by default
	*/
	public function testAllowSchemeHTTPS()
	{
		$urlConfig = $this->urlConfig->toConfig();

		$this->assertArrayHasKey('allowedSchemes', $urlConfig);
		$this->assertRegexp($urlConfig['allowedSchemes'], 'https');
	}

	/**
	* @testdox "HTTPS" is an allowed scheme by default
	*/
	public function testAllowSchemeHTTPSCaseInsensitive()
	{
		$urlConfig = $this->urlConfig->toConfig();

		$this->assertArrayHasKey('allowedSchemes', $urlConfig);
		$this->assertRegexp($urlConfig['allowedSchemes'], 'HTTPS');
	}

	/**
	* @testdox "ftp" is an allowed scheme by default
	*/
	public function testDisallowedSchemeFTP()
	{
		$urlConfig = $this->urlConfig->toConfig();

		$this->assertArrayHasKey('allowedSchemes', $urlConfig);
		$this->assertNotRegexp($urlConfig['allowedSchemes'], 'ftp');
	}

	/**
	* @testdox getAllowedSchemes() returns an array containing all the allowed schemes
	*/
	public function testGetAllowedSchemes()
	{
		$this->assertEquals(
			array('http', 'https'),
			$this->urlConfig->getAllowedSchemes()
		);
	}

	/**
	* @testdox allowScheme('ftp') will allow "ftp" as scheme
	*/
	public function testAllowSchemeFTP()
	{
		$this->urlConfig->allowScheme('ftp');
		$urlConfig = $this->urlConfig->toConfig();

		$this->assertArrayHasKey('allowedSchemes', $urlConfig);
		$this->assertRegexp($urlConfig['allowedSchemes'], 'ftp');
	}

	/**
	* @testdox allowScheme('<invalid>') throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid scheme name '<invalid>'
	*/
	public function testInvalidAllowScheme()
	{
		$this->urlConfig->allowScheme('<invalid>');
	}

	/**
	* @testdox There is no default scheme by default
	*/
	public function testNoDefaultScheme()
	{
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertArrayNotHasKey('defaultScheme', $urlConfig);
	}

	/**
	* @testdox setDefaultScheme('http') sets "http" as default scheme
	*/
	public function testSetDefaultScheme()
	{
		$this->urlConfig->setDefaultScheme('http');
		$urlConfig = $this->urlConfig->toConfig();
		$this->assertArrayHasKey('defaultScheme', $urlConfig);
		$this->assertSame('http', $urlConfig['defaultScheme']);
	}

	/**
	* @testdox setDefaultScheme('<invalid>') throws an exception
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid scheme name '<invalid>'
	*/
	public function testInvalidDefaultScheme()
	{
		$this->urlConfig->setDefaultScheme('<invalid>');
	}
}