<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript;

use s9e\TextFormatter\Configurator\JavaScript\FunctionProvider;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\FunctionProvider
*/
class FunctionProviderTest extends Test
{
	protected function tearDown(): void
	{
		unset(FunctionProvider::$cache['foo']);
	}

	/**
	* @testdox get() will return the source from cache if available
	*/
	public function testReturnFromCache()
	{
		FunctionProvider::$cache['foo'] = 'alert(1)';
		$this->assertSame('alert(1)', FunctionProvider::get('foo'));
	}

	/**
	* @testdox get() will return the source from the filesystem if applicable
	*/
	public function testReturnFromFilesystem()
	{
		unset(FunctionProvider::$cache['foo']);
		$filepath = __DIR__ . '/../../../src/Configurator/JavaScript/functions/foo.js';
		self::$tmpFiles[] = $filepath;
		file_put_contents($filepath, 'alert(2)');
		$this->assertSame('alert(2)', FunctionProvider::get('foo'));
	}

	/**
	* @testdox get() will throw an exception if the function can't be sourced
	*/
	public function testInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Unknown function 'foobar'");

		unset(FunctionProvider::$cache['foobar']);
		FunctionProvider::get('foobar');
	}
}