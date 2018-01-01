<?php

namespace s9e\TextFormatter\Tests\Utils\Http\Clients;

use s9e\TextFormatter\Utils\Http\Clients\Cached;
use s9e\TextFormatter\Utils\Http\Clients\Native;

/**
* @covers s9e\TextFormatter\Utils\Http\Clients\Cached
*/
class CachedTest extends AbstractTest
{
	public static function tearDownAfterClass()
	{
		array_map('unlink', glob(sys_get_temp_dir() . '/http.*.gz'));
	}

	protected function getInstance()
	{
		$client = new Cached(new Native);
		$client->cacheDir = sys_get_temp_dir();

		return $client;
	}

	/**
	* @testdox Settings from the proxied client are copied
	*/
	public function testProxyClientSettingCopied()
	{
		$native = new Native;
		$native->sslVerifyPeer = true;
		$native->timeout       = 123;

		$cached = new Cached($native);
		$this->assertTrue($cached->sslVerifyPeer);
		$this->assertSame(123, $cached->timeout);
	}

	/**
	* @testdox Settings from the caching client are copied to the proxied client
	*/
	public function testCachingClientSettingCopied()
	{
		$native = new Native;
		$cached = new Cached($native);
		$cached->sslVerifyPeer = true;
		$cached->timeout       = 123;
		$cached->get($this->url);

		$this->assertTrue($native->sslVerifyPeer);
		$this->assertSame(123, $native->timeout);
	}

	/**
	* @testdox Can work without a cache dir
	*/
	public function testNoCache()
	{
		$client = $this->getInstance();
		$client->cacheDir = null;

		$this->assertNotEmpty($client->get($this->url));
	}

	/**
	* @testdox Works with a cache
	*/
	public function testCache()
	{
		$client = $this->getInstance();

		$url = $this->url . '?uniqid=' . mt_rand();
		$this->assertSame($client->get($url), $client->get($url));
	}
}