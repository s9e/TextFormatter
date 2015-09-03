<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript\Minifiers;

use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension json
* @covers s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService
*/
class ClosureCompilerServiceTest extends Test
{
	public function setUp()
	{
		stream_wrapper_unregister('http');
		stream_wrapper_register('http', __NAMESPACE__ . '\\ClosureCompilerServiceProxy');
	}

	public function tearDown()
	{
		stream_wrapper_restore('http');
	}

	/**
	* @testdox Works
	*/
	public function testBasic()
	{
		$original =
			"function hello(name) {
				alert('Hello, ' + name);
			}
			hello('New user')";

		$expected = 'alert("Hello, New user");';

		$minifier = new ClosureCompilerService;
		$this->assertSame($expected, $minifier->minify($original));
	}

	protected function getQueryParams($src)
	{
		$minifier = new ClosureCompilerService;
		$minifier->minify('');

		$content = ClosureCompilerServiceProxy::$lastContext['http']['content'];
		$params  = [];
		foreach (explode('&', $content) as $pair)
		{
			$pair    = explode('=', $pair);
			$pair[1] = urldecode($pair[1]);

			$params[] = $pair;
		}

		return $params;
	}

	/**
	* @testdox Compilation level is ADVANCED_OPTIMIZATIONS by default
	*/
	public function testCompilationLevelDefault()
	{
		$params = $this->getQueryParams('');

		$this->assertContains(
			['compilation_level', 'ADVANCED_OPTIMIZATIONS'],
			$params
		);
	}

	/**
	* @testdox Excludes default externs by default
	*/
	public function testExcludesDefaultExternsByDefault()
	{
		$params = $this->getQueryParams('');

		$this->assertContains(
			['exclude_default_externs', 'true'],
			$params
		);
	}

	/**
	* @testdox Includes our custom externs by default
	*/
	public function testCustomExterns()
	{
		$params  = $this->getQueryParams('');
		$externs = file_get_contents(__DIR__ . '/../../../../src/Configurator/JavaScript/externs.service.js');

		$this->assertContains(
			['js_externs', $externs],
			$params
		);
	}

	/**
	* @testdox Allows caching
	*/
	public function testAllowsCaching()
	{
		$minifier = new ClosureCompilerService;

		$this->assertNotSame(false, $minifier->getCacheDifferentiator());
	}

	/**
	* @testdox The cache key depends on the compilation level
	*/
	public function testCacheKeyCompilationLevel()
	{
		$minifier = new ClosureCompilerService;

		$minifier->compilationLevel = 'ADVANCED_OPTIMIZATIONS';
		$k1 = $minifier->getCacheDifferentiator();

		$minifier->compilationLevel = 'SIMPLE_OPTIMIZATIONS';
		$k2 = $minifier->getCacheDifferentiator();

		$this->assertNotEquals($k1, $k2);
	}

	/**
	* @testdox The cache key depends on whether the default externs are excluded
	*/
	public function testCacheKeyDefaultExterns()
	{
		$minifier = new ClosureCompilerService;

		$minifier->excludeDefaultExterns = true;
		$k1 = $minifier->getCacheDifferentiator();

		$minifier->excludeDefaultExterns = false;
		$k2 = $minifier->getCacheDifferentiator();

		$this->assertNotEquals($k1, $k2);
	}

	/**
	* @testdox If the default externs are excluded, the custom externs are baked into the cache key
	*/
	public function testCacheKeyCustomExterns()
	{
		$minifier = new ClosureCompilerService;
		$minifier->excludeDefaultExterns = true;

		$this->assertTrue(in_array($minifier->externs, $minifier->getCacheDifferentiator(), true));
	}

	/**
	* @testdox Throws an exception in case of a request failure
	* @expectedException RuntimeException
	* @expectedExceptionMessage Could not contact the Closure Compiler service
	*/
	public function testRequestFailure()
	{
		$minifier = new ClosureCompilerService;
		$minifier->url = 'data:text/plain,';

		$minifier->minify('alert()');
	}

	/**
	* @testdox Throws an exception if the response isn't valid JSON
	* @expectedException RuntimeException
	* @expectedExceptionMessage Closure Compiler service returned invalid JSON: Syntax error
	*/
	public function testJSONError()
	{
		$minifier = new ClosureCompilerService;
		$minifier->url = 'data:text/plain,foo';

		$minifier->minify('alert()');
	}

	/**
	* @testdox Throws an exception in case of a server error
	* @expectedException RuntimeException
	* @expectedExceptionMessage Server error 4: Unknown compression level: UNKNOWN
	*/
	public function testServerError()
	{
		$minifier = new ClosureCompilerService;
		$minifier->compilationLevel = 'UNKNOWN';

		$minifier->minify('alert()');
	}

	/**
	* @testdox Throws an exception in case of a compilation error
	* @expectedException RuntimeException
	* @expectedExceptionMessage Parse error. Semi-colon expected
	*/
	public function testCompilationError()
	{
		$minifier = new ClosureCompilerService;

		$minifier->minify('This should fail');
	}
}

class ClosureCompilerServiceProxy
{
	public static $lastContext;
	protected $response;

	public function stream_open($url)
	{
		self::$lastContext = stream_context_get_options($this->context);

		$id        = sprintf('%08X', crc32(serialize(self::$lastContext)));
		$cacheFile = __DIR__ . '/cache/' . $id;

		if (file_exists($cacheFile))
		{
			$this->response = unserialize(file_get_contents($cacheFile));
		}
		else
		{
			stream_wrapper_restore('http');

			$this->response = file_get_contents($url, false, $this->context);
			file_put_contents($cacheFile, serialize($this->response));
		}

		return true;
	}

	public function stream_stat()
	{
		return false;
	}

	public function stream_read($maxLen)
	{
		$chunk = substr($this->response, 0, $maxLen);
		$this->response = substr($this->response, $maxLen);

		return $chunk;
	}

	public function stream_eof()
	{
		return ($this->response === false);
	}
}