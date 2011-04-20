<?php

namespace s9e\Toolkit\Tests\TextFormatter;

use s9e\Toolkit\Tests\Test;

include_once __DIR__ . '/../Test.php';

class AutoloadersTest extends Test
{
	/**
	* @test
	* @runInSeparateProcess
	*/
	public function ConfigBuilder_loads_core_plugins_files()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Plugins\\BBCodesConfig',
			$this->cb->loadPlugin('BBCodes')
		);
	}

	/**
	* @test
	* @runInSeparateProcess
	*/
	public function ConfigBuilder_can_autoload_Parser()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Parser',
			$this->cb->getParser()
		);
	}

	/**
	* @test
	* @runInSeparateProcess
	* @depends ConfigBuilder_can_autoload_Parser
	*/
	public function ConfigBuilder_does_not_include_Parser_twice()
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
	* @test
	* @runInSeparateProcess
	*/
	public function ConfigBuilder_can_autoload_Renderer()
	{
		$this->assertInstanceOf(
			's9e\\Toolkit\\TextFormatter\\Renderer',
			$this->cb->getRenderer()
		);
	}

	/**
	* @test
	* @runInSeparateProcess
	* @depends ConfigBuilder_can_autoload_Renderer
	*/
	public function ConfigBuilder_does_not_include_Renderer_twice()
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