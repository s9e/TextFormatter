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
	public function setUp()
	{
		array_map('unlink', self::getCacheFiles());
	}

	public static function tearDownAfterClass()
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

		$stub = $this->getMock(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Minifier',
			['minify']
		);
		$stub->expects($this->once())
		     ->method('minify')
		     ->with($original)
		     ->will($this->returnValue($expected));

		$this->assertSame($expected, $stub->get($original));
	}

	/**
	* @testdox Caching is disabled by default
	*/
	public function testNoCache()
	{
		$minifier = new DummyNonCachingMinifier;
		$minifier->cacheDir = sys_get_temp_dir();

		$minifier->get("alert('Hello world')");

		$this->assertEmpty(self::getCacheFiles());
	}

	/**
	* @testdox Caching is enabled if cacheDir is set and the minifier implements getCacheDifferentiator()
	*/
	public function testCache()
	{
		$minifier = new DummyCachingMinifier;
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
			sys_get_temp_dir() . '/minifier.35939e11e190f97da4cc7f5eee7b86e056812f93.js',
			'alert("From cache")'
		);

		$minifier = new DummyCachingMinifier;
		$minifier->cacheDir = sys_get_temp_dir();

		$this->assertSame(
			'alert("From cache")',
			$minifier->get("alert('Hello world')")
		);
	}

	/**
	* @testdox get() rethrows exception thrown during minification by default
	* @expectedException Exception foo
	*/
	public function testGetRethrow()
	{
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
}

class DummyNonCachingMinifier extends Minifier
{
	public function minify($src)
	{
		return $src;
	}
}

class DummyCachingMinifier extends Minifier
{
	public function minify($src)
	{
		return $src;
	}

	public function getCacheDifferentiator()
	{
		return 'foo';
	}
}

class DummyThrowingMinifier extends Minifier
{
	public function minify($src)
	{
		throw new Exception('foo');
	}
}