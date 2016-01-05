<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript\Minifiers;

use s9e\TextFormatter\Configurator\JavaScript\Minifiers\HostedMinifier;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\RemoteCache;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\Minifiers\RemoteCache
* @group needs-network
*/
class RemoteCacheTest extends Test
{
	/**
	* @testdox minify() works
	*/
	public function testWorks()
	{
		// We need to ensure that the result is in cache
		$minifier = new HostedMinifier;
		$minifier->minify('alert("s9e"+"!");');

		$minifier = new RemoteCache;
		$this->assertEquals('alert("s9e!");', $minifier->minify('alert("s9e"+"!");'));
	}

	/**
	* @testdox Throws an exception if the minification fails
	* @expectedException RuntimeException
	*/
	public function testFails()
	{
		$minifier = new RemoteCache;
		$minifier->minify(uniqid('This should fail'));
	}
}