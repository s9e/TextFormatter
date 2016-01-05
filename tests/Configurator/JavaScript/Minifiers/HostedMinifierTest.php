<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript\Minifiers;

use s9e\TextFormatter\Configurator\JavaScript\Minifiers\HostedMinifier;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\Minifiers\HostedMinifier
* @group needs-network
*/
class HostedMinifierTest extends Test
{
	/**
	* @testdox minify() works
	*/
	public function testWorks()
	{
		$minifier = new HostedMinifier;
		$this->assertSame('alert("s9e!");', $minifier->minify('alert("s9e"+"!");'));
	}

	/**
	* @testdox Throws an exception if the minification fails
	* @expectedException RuntimeException
	*/
	public function testFails()
	{
		$minifier = new HostedMinifier;
		$minifier->minify('This should fail');
	}
}