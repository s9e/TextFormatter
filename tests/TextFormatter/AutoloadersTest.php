<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../Test.php';

class AutoloadersTest extends Test
{
	/**
	* @runInSeparateProcess
	*/
	public function testConfigBuilderLoadsCorePluginsFiles()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Plugins\\BBCodesConfig',
			$this->cb->loadPlugin('BBCodes')
		);
	}

	/**
	* @runInSeparateProcess
	*/
	public function testConfigBuilderCanAutoloadParser()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Parser',
			$this->cb->getParser()
		);
	}

	/**
	* @runInSeparateProcess
	* @depends testConfigBuilderCanAutoloadParser
	*/
	public function testConfigBuilderDoesNotIncludeParserTwice()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Parser',
			$this->cb->getParser()
		);
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Parser',
			$this->cb->getParser()
		);
	}

	/**
	* @runInSeparateProcess
	*/
	public function testConfigBuilderCanAutoloadRenderer()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Renderer',
			$this->cb->getRenderer()
		);
	}

	/**
	* @runInSeparateProcess
	* @depends testConfigBuilderCanAutoloadRenderer
	*/
	public function testConfigBuilderDoesNotIncludeRendererTwice()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Renderer',
			$this->cb->getRenderer()
		);
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Renderer',
			$this->cb->getRenderer()
		);
	}
}