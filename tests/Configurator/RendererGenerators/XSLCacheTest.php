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
}