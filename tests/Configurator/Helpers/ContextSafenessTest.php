<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Configurator\Helpers\ContextSafeness;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\ContextSafeness
*/
class ContextSafenessTest extends Test
{
	/**
	* @testdox getDisallowedCharactersAsURL() returns a list of strings
	*/
	public function testGetDisallowedCharactersAsURL ()
	{
		$disallowedChars = ContextSafeness::getDisallowedCharactersAsURL();
		$this->assertIsArray($disallowedChars);

		foreach ($disallowedChars as $char)
		{
			$this->assertIsString($char);
		}
	}

	/**
	* @testdox getDisallowedCharactersInCSS() returns a list of strings
	*/
	public function testGetDisallowedCharactersInCSS ()
	{
		$disallowedChars = ContextSafeness::getDisallowedCharactersInCSS();
		$this->assertIsArray($disallowedChars);

		foreach ($disallowedChars as $char)
		{
			$this->assertIsString($char);
		}
	}

	/**
	* @testdox getDisallowedCharactersInJS() returns a list of strings
	*/
	public function testGetDisallowedCharactersInJS ()
	{
		$disallowedChars = ContextSafeness::getDisallowedCharactersInJS();
		$this->assertIsArray($disallowedChars);

		foreach ($disallowedChars as $char)
		{
			$this->assertIsString($char);
		}
	}
}