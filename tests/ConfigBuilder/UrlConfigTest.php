<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder;

use s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder\UrlConfig;

include_once __DIR__ . '/../../src/autoloader.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder\UrlConfig
*/
class UrlConfigTest extends Test
{
	public function setUp()
	{
		$this->urlConfig = new UrlConfig;
	}

	public function getUrlConfig()
	{
		return $this->urlConfig->getConfig();
	}

	/**
	* @test
	*/
	public function HTTP_and_HTTPS_schemes_are_allowed_by_default()
	{
		$this->assertEquals(
			array('http', 'https'),
			$this->urlConfig->getAllowedSchemes()
		);
	}

	/**
	* @testdox allowScheme() can be used to allow additional schemes
	*/
	public function testCanAllowAdditionalSchemes()
	{
		// first we check that the regexp isn't borked and doesn't allow just about anything
		$urlConfig = $this->getUrlConfig();
		$this->assertNotRegexp($urlConfig['allowedSchemes'], 'foo');

		$this->urlConfig->allowScheme('foo');

		$urlConfig = $this->getUrlConfig();
		$this->assertRegexp($urlConfig['allowedSchemes'], 'foo');
	}

	/**
	* @testdox allowScheme() throws an exception on invalid scheme names
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid scheme name 'foo:bar'
	*/
	public function testInvalidSchemeNames()
	{
		$this->urlConfig->allowScheme('foo:bar');
	}

	/**
	* @testdox There is no default scheme for schemeless URLs by default
	*/
	public function testThereIsNoDefaultSchemeForSchemelessURLsByDefault()
	{
		$urlConfig = $this->getUrlConfig();
		$this->assertArrayNotHasKey('defaultScheme', $urlConfig);
	}

	/**
	* @testdox setDefaultScheme() can set a default scheme to be used for URLs with no scheme
	*/
	public function testADefaultSchemeCanBeSetForSchemelessURLs()
	{
		$this->urlConfig->setDefaultScheme('http');
		$urlConfig = $this->getUrlConfig();

		$this->assertArrayMatches(
			array('defaultScheme' => 'http'),
			$urlConfig
		);
	}

	public function testCanDisallowHosts()
	{
		$this->urlConfig->disallowHost('example.org');

		$urlConfig = $this->getUrlConfig();

		$this->assertArrayHasKey('disallowedHosts', $urlConfig);

		$this->assertRegexp($urlConfig['disallowedHosts'], 'example.org');
	}

	public function testCanDisallowHosts2()
	{
		$this->urlConfig->disallowHost('*warez*');

		$urlConfig = $this->getUrlConfig();

		$this->assertArrayHasKey('disallowedHosts', $urlConfig);

		$this->assertRegexp($urlConfig['disallowedHosts'], 'www.superwarez.example.org');
	}

	/**
	* @test
	*/
	public function Url_filter_can_be_configured_to_resolve_redirects_from_a_given_host()
	{
		$this->urlConfig->resolveRedirectsFrom('bit.ly');

		$urlConfig = $this->getUrlConfig();

		$this->assertArrayHasKey('resolveRedirectsHosts', $urlConfig);

		$this->assertRegexp($urlConfig['resolveRedirectsHosts'], 'bit.ly');
	}

	/**
	* @test
	*/
	public function Disallowed_IDNs_are_punycoded()
	{
		$this->urlConfig->disallowHost('pаypal.com');

		$urlConfig = $this->getUrlConfig();

		$this->assertArrayHasKey('disallowedHosts', $urlConfig);

		$this->assertContains(
			'xn--pypal-4ve\\.com',
			$urlConfig['disallowedHosts']
		);
	}
}