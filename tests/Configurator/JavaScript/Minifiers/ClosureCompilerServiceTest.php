<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript\Minifiers;

use RuntimeException;
use s9e\TextFormatter\Utils\Http\Client;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension json
* @covers s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerService
*/
class ClosureCompilerServiceTest extends Test
{
	/**
	* @testdox Works
	* @group needs-network
	*/
	public function testBasic()
	{
		if (isset($_SERVER['TRAVIS']))
		{
			$this->markTestSkipped();
		}

		$original =
			"function hello(name) {
				alert('Hello, ' + name);
			}
			hello('New user')";

		$expected = 'alert("Hello, New user");';

		$minifier = new ClosureCompilerService;
		$this->assertSame($expected, $minifier->minify($original));
	}

	/**
	* @testdox Compilation level is ADVANCED_OPTIMIZATIONS by default
	*/
	public function testCompilationLevelDefault()
	{
		$minifier = new ClosureCompilerService;
		$minifier->httpClient = new ClosureCompilerServiceTestClient;
		$minifier->minify('');

		$this->assertStringContainsString(
			'compilation_level=ADVANCED_OPTIMIZATIONS',
			$minifier->httpClient->body
		);
	}

	/**
	* @testdox Excludes default externs by default
	*/
	public function testExcludesDefaultExternsByDefault()
	{
		$minifier = new ClosureCompilerService;
		$minifier->httpClient = new ClosureCompilerServiceTestClient;
		$minifier->minify('');

		$this->assertStringContainsString(
			'exclude_default_externs=true',
			$minifier->httpClient->body
		);
	}

	/**
	* @testdox Includes our custom externs by default
	*/
	public function testCustomExterns()
	{
		$externs = file_get_contents(__DIR__ . '/../../../../src/Configurator/JavaScript/externs.service.js');

		$minifier = new ClosureCompilerService;
		$minifier->httpClient = new ClosureCompilerServiceTestClient;
		$minifier->minify('');

		$this->assertStringContainsString(
			'js_externs=' . urlencode($externs),
			$minifier->httpClient->body
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
	*/
	public function testRequestFailure()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Could not contact the Closure Compiler service');

		$minifier = new ClosureCompilerService;
		$minifier->httpClient = new ClosureCompilerServiceTestClient;
		$minifier->httpClient->willReturn = false;
		$minifier->minify('');
	}

	/**
	* @testdox Throws an exception if the response isn't valid JSON
	*/
	public function testJSONError()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Closure Compiler service returned invalid JSON: Syntax error');

		$minifier = new ClosureCompilerService;
		$minifier->httpClient = new ClosureCompilerServiceTestClient;
		$minifier->httpClient->willReturn = 'not JSON';
		$minifier->minify('');
	}

	/**
	* @testdox Throws an exception in case of a server error
	*/
	public function testServerError()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Server error 4: Unknown compression level: UNKNOWN');

		$minifier = new ClosureCompilerService;
		$minifier->compilationLevel = 'UNKNOWN';
		$minifier->httpClient = new ClosureCompilerServiceTestClient;
		$minifier->httpClient->willReturn = '{"serverErrors":[{"code":4,"error":"Unknown compression level: UNKNOWN."}]}';

		$minifier->minify('alert()');
	}

	/**
	* @testdox Throws an exception in case of a compilation error
	*/
	public function testCompilationError()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Parse error. Semi-colon expected');

		$minifier = new ClosureCompilerService;
		$minifier->httpClient = new ClosureCompilerServiceTestClient;
		$minifier->httpClient->willReturn = '{"compiledCode":"","errors":[{"type":"JSC_PARSE_ERROR","file":"Input_0","lineno":1,"charno":5,"error":"Parse error. Semi-colon expected","line":"This should fail"}]}';

		$minifier->minify('This should fail');
	}
}

class ClosureCompilerServiceTestClient extends Client
{
	public $body;
	public $options;
	public $url;
	public $willReturn = '{"compiledCode":""}';

	public function get($url, array $options = [])
	{
		$this->url     = $url;
		$this->options = $options;

		return $this->willReturn;
	}

	public function post($url, array $options = [], $body = '')
	{
		$this->url     = $url;
		$this->options = $options;
		$this->body    = $body;

		return $this->willReturn;
	}
}