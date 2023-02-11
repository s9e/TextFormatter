<?php

namespace s9e\TextFormatter\Tests\Utils\Http\Clients;

use s9e\TextFormatter\Utils\Http\Clients\Native;

class NativeTest extends AbstractTestClass
{
	protected function getInstance()
	{
		return new Native;
	}

	/**
	* @testdox Does not send "Accept-Encoding: gzip" if gzip is disabled
	*/
	public function testNoGzip()
	{
		$client = $this->getInstance();
		$client->gzipEnabled = false;
		$vars = unserialize($client->get($this->url));
		$this->assertArrayNotHasKey('HTTP_ACCEPT_ENCODING', $vars['_SERVER']);
	}
}