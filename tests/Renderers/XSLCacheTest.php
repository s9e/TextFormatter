<?php

namespace s9e\TextFormatter\Tests\Renderers;

use s9e\TextFormatter\Configurator\RendererGenerators\XSLCache as XSLCacheGenerator;

/**
* @requires extension xslcache
* @covers s9e\TextFormatter\Renderer
* @covers s9e\TextFormatter\Renderers\XSLCache
*/
class XSLCacheTest extends XSLTTest
{
	public function setUp()
	{
		$this->configurator->rendererGenerator = new XSLCacheGenerator(sys_get_temp_dir());
	}

	/**
	* @testdox getFilepath() returns the path to the stylesheet file
	*/
	public function testGetFilepath()
	{
		$this->assertFileExists($this->configurator->getRenderer()->getFilepath());
	}
}