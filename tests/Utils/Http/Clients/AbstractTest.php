<?php

namespace s9e\TextFormatter\Tests\Utils\Http\Clients;

use s9e\TextFormatter\Tests\Test;

abstract class AbstractTest extends Test
{
	abstract protected function getInstance();

	protected $url = 'http://localhost/reflect.php';

	protected function setUp(): void
	{
		parent::setUp();
		if (!empty($_SERVER['TRAVIS']))
		{
			$this->url = str_replace('localhost', 'localhost:8000', $this->url);
		}
		if (!@file_get_contents($this->url))
		{
			$this->markTestSkipped('Cannot access ' . $this->url);
		}
	}

	/**
	* @testdox Uses gzip by default if ext/zlib is availble
	* @requires extension zlib
	*/
	public function testUsesGzipDefault()
	{
		$client = $this->getInstance();
		$vars = unserialize($client->get($this->url));
		$this->assertArrayHasKey('HTTP_ACCEPT_ENCODING', $vars['_SERVER']);
		$this->assertStringContainsString('gzip', $vars['_SERVER']['HTTP_ACCEPT_ENCODING']);
	}

	/**
	* @testdox Sends custom headers
	*/
	public function testSendsCustomHeader()
	{
		$client = $this->getInstance();
		$vars = unserialize($client->get($this->url, ['headers' => ['X-Foo: bar']]));
		$this->assertArrayHasKey('HTTP_X_FOO', $vars['_SERVER']);
		$this->assertEquals('bar', $vars['_SERVER']['HTTP_X_FOO']);
	}

	/**
	* @testdox Resets custom headers between requests
	*/
	public function testResetsCustomHeader()
	{
		$client = $this->getInstance();
		$vars = unserialize($client->get($this->url, ['headers' => ['X-Foo: bar']]));
		$this->assertArrayHasKey('HTTP_X_FOO', $vars['_SERVER']);
		$vars = unserialize($client->get($this->url));
		$this->assertArrayNotHasKey('HTTP_X_FOO', $vars['_SERVER']);
	}

	/**
	* @testdox post() sends the request body if set
	*/
	public function testPostSendsRequestBody()
	{
		$client = $this->getInstance();
		$vars = unserialize($client->post(
			$this->url,
			['headers' => ['Content-Type: application/octet-stream']],
			'Foo'
		));
		$this->assertEquals('Foo', $vars['input']);
	}

	/**
	* @testdox post() sends no request body if not set
	*/
	public function testPostSendsNoRequestBody()
	{
		$client = $this->getInstance();
		$client->post(
			$this->url,
			['headers' => ['Content-Type: application/octet-stream']],
			'Foo'
		);
		$vars = unserialize($client->post(
			$this->url,
			['headers' => ['Content-Type: application/octet-stream']]
		));
		$this->assertSame('', $vars['input']);
	}

	/**
	* @testdox post() automatically sets Content-Length if a request body is set
	*/
	public function testPostContentLength()
	{
		$client = $this->getInstance();
		$vars = unserialize($client->post(
			$this->url,
			['headers' => ['Content-Type: application/octet-stream']],
			'Foo'
		));
		$this->assertEquals(3, $vars['_SERVER']['HTTP_CONTENT_LENGTH']);
	}

	/**
	* @testdox get() returns FALSE on error
	*/
	public function testReturnsFalse()
	{
		$this->assertFalse($this->getInstance()->get(str_replace('reflect.php', '404', $this->url)));
	}

	/**
	* @testdox get() returns headers if returnHeaders is true
	*/
	public function testGetReturnHeaders()
	{
		$client = $this->getInstance();
		$this->assertMatchesRegularExpression(
			'(^HTTP[^\\r]++\\r\\n(?:[-\\w]+:[^\\r]++\\r\\n)+\\r\\na:)',
			$client->get($this->url, ['returnHeaders' => true])
		);
	}
}