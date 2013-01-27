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
}

class ClosureCompilerServiceProxy
{
	protected $eof = false;
	protected $url;

	public function __construct()
	{
	}

	public function stream_open($url)
	{
		$this->url = $url;

		return true;
	}

	public function stream_stat()
	{
		return false;
	}

	public function stream_read()
	{
		if ($this->eof)
		{
			return '';
		}
		$this->eof = true;

		$id        = sprintf('%08X', crc32(serialize(stream_context_get_options($this->context))));
		$cacheFile = __DIR__ . '/cache/' . $id;

		if (file_exists($cacheFile))
		{
			return unserialize(file_get_contents($cacheFile));
		}

		stream_wrapper_restore('http');

		$response = file_get_contents($this->url, false, $this->context);
		file_put_contents($cacheFile, serialize($response));

		return $response;
	}

	public function stream_eof()
	{
		return $this->eof;
	}
}