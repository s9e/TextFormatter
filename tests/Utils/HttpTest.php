<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Utils\Http;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Utils\Http
*/
class HttpTest extends Test
{
	/**
	* @testdox getClient() returns an instance of s9e\TextFormatter\Utils\Http\Client
	*/
	public function testGetClient()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Utils\\Http\\Client',
			Http::getClient()
		);
	}

	/**
	* @testdox getCachingClient() returns an instance of s9e\TextFormatter\Utils\Http\Clients\Cached that implements s9e\TextFormatter\Utils\Http\Client
	*/
	public function testGetCachingClient()
	{
		$client = Http::getCachingClient();
		$this->assertInstanceOf('s9e\\TextFormatter\\Utils\\Http\\Client',          $client);
		$this->assertInstanceOf('s9e\\TextFormatter\\Utils\\Http\\Clients\\Cached', $client);
	}
}