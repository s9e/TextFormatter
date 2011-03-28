<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer,
    s9e\Toolkit\TextFormatter\Plugins\BBCodesConfig;

include_once __DIR__ . '/../Test.php';

class AutoloadersTest extends Test
{
	/**
	* @runInSeparateProcess
	*/
	public function testConfigBuilderLoadsCorePluginsFiles()
	{
		$this->assertTrue($this->cb->loadPlugin('BBCodes') instanceof BBCodesConfig);
	}

	/**
	* @runInSeparateProcess
	*/
	public function testConfigBuilderCanAutoloadParser()
	{
		$this->assertTrue($this->cb->getParser() instanceof Parser);
	}

	/**
	* @runInSeparateProcess
	* @depends testConfigBuilderCanAutoloadParser
	*/
	public function testConfigBuilderDoesNotIncludeParserTwice()
	{
		$this->assertTrue($this->cb->getParser() instanceof Parser);
		$this->assertTrue($this->cb->getParser() instanceof Parser);
	}

	/**
	* @runInSeparateProcess
	*/
	public function testConfigBuilderCanAutoloadRenderer()
	{
		$this->assertTrue($this->cb->getRenderer() instanceof Renderer);
	}

	/**
	* @runInSeparateProcess
	* @depends testConfigBuilderCanAutoloadRenderer
	*/
	public function testConfigBuilderDoesNotIncludeRendererTwice()
	{
		$this->assertTrue($this->cb->getRenderer() instanceof Renderer);
		$this->assertTrue($this->cb->getRenderer() instanceof Renderer);
	}
}