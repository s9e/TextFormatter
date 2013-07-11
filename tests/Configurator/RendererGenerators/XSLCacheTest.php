<?php

namespace s9e\TextFormatter\Tests\Configurator\RendererGenerators;

use s9e\TextFormatter\Configurator\RendererGenerators\XSLCache;
use s9e\TextFormatter\Tests\Test;

/**
* @requires extension xslcache
* @covers s9e\TextFormatter\Configurator\RendererGenerators\XSLCache
*/
class XSLCacheTest extends Test
{
	/**
	* @testdox Returns an instance of Renderer
	*/
	public function testInstance()
	{
		$generator = new XSLCache(sys_get_temp_dir());
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Renderer',
			$generator->getRenderer($this->configurator->stylesheet)
		);
	}

	/**
	* @testdox The path given to the constructor is canonicalized
	*/
	public function testRealpath()
	{
		$generator = new XSLCache(__DIR__ . '/../' . basename(__DIR__));
		$this->assertAttributeSame(__DIR__, 'cacheDir', $generator);
	}

	/**
	* @testdox The constructor throws an exception if the given path does not exist
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Path '/does/not/exist' is invalid
	*/
	public function testInvalidPath()
	{
		new XSLCache('/does/not/exist');
	}
}