<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers\Http\Clients;

use s9e\TextFormatter\Tests\Test;

abstract class AbstractTest extends Test
{
	abstract protected function getInstance();

	protected $url = 'http://localhost/reflect.php';

	public function setUp()
	{
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
		$this->assertContains('gzip', $vars['_SERVER']['HTTP_ACCEPT_ENCODING']);
	}

	/**
	* @testdox Sends custom headers
	*/
	public function testSendsCustomHeader()
	{
		$client = $this->getInstance();
		$vars = unserialize($client->get($this->url, ['X-Foo: bar']));
		$this->assertEquals('bar', $vars['_SERVER']['HTTP_X_FOO']);
	}

	/**
	* @testdox post() sends the request body if set
	*/
	public function testPostSendsRequestBody()
	{
		$client = $this->getInstance();
		$vars = unserialize($client->post(
			$this->url,
			['Content-Type: application/octet-stream'],
			'Foo'
		));
		$this->assertEquals('Foo', $vars['input']);
	}

	/**
	* @testdox post() automatically sets Content-Length if a request body is set
	*/
	public function testPostContentLength()
	{
		$client = $this->getInstance();
		$vars = unserialize($client->post(
			$this->url,
			['Content-Type: application/octet-stream'],
			'Foo'
		));
		$this->assertEquals(3, $vars['_SERVER']['HTTP_CONTENT_LENGTH']);
	}
}