<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript\Minifiers;

use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService;
use s9e\TextFormatter\Tests\Test;

/**
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
		$externs = file_get_contents(__DIR__ . '/../../../../src/s9e/TextFormatter/Configurator/JavaScript/externs.js');

		$this->assertContains(
			['js_externs', $externs],
			$params
		);
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