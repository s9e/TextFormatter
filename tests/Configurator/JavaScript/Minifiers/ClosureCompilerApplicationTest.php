<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript\Minifiers;

use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerApplication;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerApplication
*/
class ClosureCompilerApplicationTest extends Test
{
	/**
	* @testdox Throws an exception if the filename passed to the constructor does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot find Closure Compiler at /does/not/exist
	*/
	public function testInvalidConstructor()
	{
		new ClosureCompilerApplication('/does/not/exist');
	}

	/**
	* @testdox Allows caching
	*/
	public function testAllowsCaching()
	{
		$minifier = new ClosureCompilerApplication(__FILE__);

		$this->assertNotSame(false, $minifier->getCacheDifferentiator());
	}

	/**
	* @testdox The cache key depends on the compilation level
	*/
	public function testCacheKeyCompilationLevel()
	{
		$minifier = new ClosureCompilerApplication(__FILE__);

		$minifier->compilationLevel = 'ADVANCED_OPTIMIZATIONS';
		$k1 = $minifier->getCacheDifferentiator();

		$minifier->compilationLevel = 'SIMPLE_OPTIMIZATIONS';
		$k2 = $minifier->getCacheDifferentiator();

		$this->assertNotEquals($k1, $k2);
	}

	/**
	* @testdox The cache key depends on the extra options
	*/
	public function testCacheKeyOptions()
	{
		$minifier = new ClosureCompilerApplication(__FILE__);

		$minifier->options = '--use_types_for_optimization';
		$k1 = $minifier->getCacheDifferentiator();

		$minifier->options = '';
		$k2 = $minifier->getCacheDifferentiator();

		$this->assertNotEquals($k1, $k2);
	}

	/**
	* @testdox The cache key depends on the Closure Compiler file
	*/
	public function testCacheKeyApplication()
	{
		$minifier = new ClosureCompilerApplication(__FILE__);
		$k1 = $minifier->getCacheDifferentiator();

		$minifier = new ClosureCompilerApplication(__DIR__ . '/NoopTest.php');
		$k2 = $minifier->getCacheDifferentiator();

		$this->assertNotEquals($k1, $k2);
	}

	/**
	* @testdox The cache key does not depend on the path to the Closure Compiler application
	*/
	public function testCacheKeyNotPath()
	{
		$minifier = new ClosureCompilerApplication(__DIR__ . '/../Minifiers/NoopTest.php');
		$k1 = $minifier->getCacheDifferentiator();

		$minifier = new ClosureCompilerApplication(__DIR__ . '/NoopTest.php');
		$k2 = $minifier->getCacheDifferentiator();

		$this->assertSame($k1, $k2);
	}

	/**
	* @testdox The cache key depends on whether the default externs are excluded
	*/
	public function testCacheKeyDefaultExterns()
	{
		$minifier = new ClosureCompilerApplication(__FILE__);

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
		$minifier = new ClosureCompilerApplication(__FILE__);
		$minifier->excludeDefaultExterns = true;

		$this->assertTrue(in_array(
			file_get_contents(__DIR__ . '/../../../../src/Configurator/JavaScript/externs.application.js'),
			$minifier->getCacheDifferentiator(),
			true
		));
	}

	/**
	* @testdox Works
	* @group slow
	*/
	public function testWorks()
	{
		$closureCompilerBin = $this->getClosureCompilerBin();
		if (!file_exists($closureCompilerBin))
		{
			$this->markTestSkipped($closureCompilerBin . ' does not exist');
		}

		$minifier = new ClosureCompilerApplication($closureCompilerBin);
		$minifier->compilationLevel = 'WHITESPACE_ONLY';
		$minifier->options = '--env=CUSTOM';

		$this->assertSame(
			'alert("Hello world");',
			$minifier->minify(' alert ( "Hello world" ) ; ')
		);
	}

	/**
	* @testdox minify() throws an exception if an error occurs during minification
	* @expectedException RuntimeException
	* @expectedExceptionMessage An error occured during minification
	* @group slow
	*/
	public function testMinifyError()
	{
		$closureCompilerBin = $this->getClosureCompilerBin();
		if (!file_exists($closureCompilerBin))
		{
			$this->markTestSkipped($closureCompilerBin . ' does not exist');
		}

		$minifier = new ClosureCompilerApplication($closureCompilerBin);
		$minifier->compilationLevel = 'WHITESPACE_ONLY';
		$minifier->options = '--env=CUSTOM';
		$minifier->minify('%error%');
	}

	/**
	* @testdox Replaces the default externs with custom externs if compilationLevel is ADVANCED_OPTIMIZATIONS and excludeDefaultExterns is true
	*/
	public function testReplacesExterns()
	{
		$minifier = new ClosureCompilerApplication(__FILE__);
		$minifier->compilationLevel = 'ADVANCED_OPTIMIZATIONS';
		$minifier->excludeDefaultExterns = true;

		// Replace the Java interpreter with a PHP script so that it outputs its own command line
		$minifier->javaBin = 'php ' . escapeshellarg(__DIR__ . '/echo.php') . ' --';

		$this->assertRegexp(
			'#--externs \\S*externs.application.js --env=CUSTOM#',
			$minifier->minify('/**/')
		);
	}
}