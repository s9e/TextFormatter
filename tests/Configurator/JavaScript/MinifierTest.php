<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript;

use Exception;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\Minifier
*/
class MinifierTest extends Test
{
	protected function setUp(): void
	{
		array_map('unlink', self::getCacheFiles());
	}

	public static function tearDownAfterClass(): void
	{
		array_map('unlink', self::getCacheFiles());
	}

	protected static function getCacheFiles()
	{
		return glob(sys_get_temp_dir() . '/minifier.*');
	}

	/**
	* @testdox get() forwards the call to minify() and returns its result
	*/
	public function testMinify()
	{
		$original = "alert('Hello world')";
		$expected = "alert('Sup world')";

		$stub = $this->getMockBuilder('s9e\\TextFormatter\\Configurator\\JavaScript\\Minifier')
		             ->setMethods(['getCacheDifferentiator', 'minify'])
		             ->getMock();
		$stub->expects($this->once())
		     ->method('minify')
		     ->with($original)
		     ->will($this->returnValue($expected));

		$this->assertSame($expected, $stub->get($original));
	}

	/**
	* @testdox Caching is disabled if cacheDir is not set
	*/
	public function testNoCache()
	{
		$minifier = new DummyMinifier;

		$minifier->get("alert('Hello world')");

		$this->assertEmpty(self::getCacheFiles());
	}

	/**
	* @testdox Caching is enabled if cacheDir is set
	*/
	public function testCache()
	{
		$minifier = new DummyMinifier;
		$minifier->cacheDir = sys_get_temp_dir();

		$minifier->get("alert('Hello world')");

		$this->assertCount(1, self::getCacheFiles());
	}

	/**
	* @testdox get() returns the cached result if applicable
	*/
	public function testFromCache()
	{
		file_put_contents(
			sys_get_temp_dir() . '/minifier.c356d10073f41560dc691043ebecfccdecb402c0.js',
			'alert("From cache")'
		);

		$minifier = new DummyMinifier;
		$minifier->cacheDir = sys_get_temp_dir();

		$this->assertSame(
			'alert("From cache")',
			$minifier->get("alert('Hello world')")
		);
	}

	/**
	* @testdox get() rethrows exception thrown during minification by default
	*/
	public function testGetRethrow()
	{
		$this->expectException('Exception');
		$this->expectExceptionMessage('foo');

		$minifier = new DummyThrowingMinifier;
		$minifier->get('alert("Hi")');
	}

	/**
	* @testdox get() discards exceptions thrown during minification and instead returns the original source if keepGoing is TRUE
	*/
	public function testGetKeepGoing()
	{
		$minifier = new DummyThrowingMinifier;
		$minifier->keepGoing = true;

		$this->assertSame(
			'alert("Hi")',
			$minifier->get('alert("Hi")')
		);
	}

	/**
	* @testdox getCacheDifferentiator() returns a default constant
	*/
	public function testGetCacheDifferentiator()
	{
		$minifier = new DummyMinifier;
		$this->assertSame(
			$minifier->getCacheDifferentiator(),
			$minifier->getCacheDifferentiator()
		);
	}
}

class DummyMinifier extends Minifier
{
	public function minify($src)
	{
		return $src;
	}
}

class DummyThrowingMinifier extends Minifier
{
	public function minify($src)
	{
		throw new Exception('foo');
	}
}