<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript\Minifiers;

use s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerApplication;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\Minifiers\ClosureCompilerApplication
*/
class ClosureCompilerApplicationTest extends Test
{
	public static function setUpBeforeClass()
	{
		file_put_contents(sys_get_temp_dir() . '/test.compiler.jar', '1');
		file_put_contents(sys_get_temp_dir() . '/test2.compiler.jar', '2');
	}

	public static function tearDownAfterClass()
	{
		unlink(sys_get_temp_dir() . '/test.compiler.jar');
		unlink(sys_get_temp_dir() . '/test2.compiler.jar');
	}

	protected function getMinifier()
	{
		$closureCompilerNative = $this->getClosureCompilerNative();
		if ($closureCompilerNative === false)
		{
			$this->markTestSkipped('Cannot find closure compiler executable');
		}

		return new ClosureCompilerApplication($closureCompilerNative);
	}

	/**
	* @testdox Constructor accepts a command
	* @group slow
	*/
	public function testConstructorCommand()
	{
		$path = realpath(__DIR__ . '/../../../../vendor/node_modules/google-closure-compiler-linux/compiler');
		if (!file_exists($path))
		{
			$this->markTestSkipped('Cannot find native compiler');
		}

		$minifier = new ClosureCompilerApplication($path);
		$minifier->compilationLevel = 'SIMPLE';
		$minifier->options = '--env=CUSTOM';
		$this->assertEquals('alert("xy");', $minifier->minify('alert("x"+"y");'));
	}

	/**
	* @testdox Constructor accepts the path to a .jar file
	* @group slow
	*/
	public function testConstructorJar()
	{
		if (isset($_SERVER['TRAVIS']) && strpos($_SERVER['JAVA_HOME'], 'java-7') !== false)
		{
			$this->markTestSkipped('Unsupported Java version');
		}

		$jar = $this->getClosureCompilerJar();
		if ($jar === false)
		{
			$this->markTestSkipped('Cannot find compiler.jar');
		}

		$minifier = new ClosureCompilerApplication($jar);
		$minifier->compilationLevel = 'SIMPLE';
		$minifier->options = '--env=CUSTOM';
		$this->assertEquals('alert("xy");', $minifier->minify('alert("x"+"y");'));
	}

	/**
	* @testdox Throws an exception if the Closure Compiler's filepath is not set at minification time
	* @expectedException RuntimeException
	* @expectedExceptionMessage No path set for Closure Compiler
	*/
	public function testNoPathRuntime()
	{
		$minifier = new ClosureCompilerApplication;
		$minifier->minify('alert(1)');
	}

	/**
	* @testdox Throws an exception if the Closure Compiler's file does not exist at minification time
	* @expectedException RuntimeException
	* @expectedExceptionMessage Cannot find Closure Compiler at /does/not/exist
	*/
	public function testInvalidPathRuntime()
	{
		$minifier = new ClosureCompilerApplication;
		$minifier->closureCompilerBin = '/does/not/exist';
		$minifier->minify('alert(1)');
	}

	/**
	* @testdox Throws an exception if the JavaScript is invalid
	* @expectedException RuntimeException
	* @expectedExceptionMessage An error occured during minification
	* @group slow
	*/
	public function testInvalidJavaScript()
	{
		$minifier = $this->getMinifier();
		$minifier->compilationLevel = 'WHITESPACE_ONLY';
		$minifier->minify('foo bar');
	}

	/**
	* @testdox Allows caching
	*/
	public function testAllowsCaching()
	{
		$minifier = new ClosureCompilerApplication(sys_get_temp_dir() . '/test.compiler.jar');

		$this->assertNotSame(false, $minifier->getCacheDifferentiator());
	}

	/**
	* @testdox The cache key depends on the compilation level
	*/
	public function testCacheKeyCompilationLevel()
	{
		$minifier = new ClosureCompilerApplication(sys_get_temp_dir() . '/test.compiler.jar');

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
		$minifier = new ClosureCompilerApplication(sys_get_temp_dir() . '/test.compiler.jar');

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
		$minifier = new ClosureCompilerApplication(sys_get_temp_dir() . '/test.compiler.jar');
		$k1 = $minifier->getCacheDifferentiator();

		$minifier = new ClosureCompilerApplication(sys_get_temp_dir() . '/test2.compiler.jar');
		$k2 = $minifier->getCacheDifferentiator();

		$this->assertNotEquals($k1, $k2);
	}

	/**
	* @testdox The cache key depends on whether the default externs are excluded
	*/
	public function testCacheKeyDefaultExterns()
	{
		$minifier = new ClosureCompilerApplication(sys_get_temp_dir() . '/test.compiler.jar');

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
		$minifier = new ClosureCompilerApplication(sys_get_temp_dir() . '/test.compiler.jar');
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
		$minifier = $this->getMinifier();
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
		$minifier = $this->getMinifier();
		$minifier->compilationLevel = 'WHITESPACE_ONLY';
		$minifier->options = '--env=CUSTOM';
		$minifier->minify('%error%');
	}

	/**
	* @testdox Replaces the default externs with custom externs if compilationLevel is ADVANCED_OPTIMIZATIONS and excludeDefaultExterns is true
	*/
	public function testReplacesExterns()
	{
		$minifier = new ClosureCompilerApplication(sys_get_temp_dir() . '/test.compiler.jar');
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