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
		$this->assertInternalType('array', $disallowedChars);

		foreach ($disallowedChars as $char)
		{
			$this->assertInternalType('string', $char);
		}
	}

	/**
	* @testdox getDisallowedCharactersInCSS() returns a list of strings
	*/
	public function testGetDisallowedCharactersInCSS ()
	{
		$disallowedChars = ContextSafeness::getDisallowedCharactersInCSS();
		$this->assertInternalType('array', $disallowedChars);

		foreach ($disallowedChars as $char)
		{
			$this->assertInternalType('string', $char);
		}
	}

	/**
	* @testdox getDisallowedCharactersInJS() returns a list of strings
	*/
	public function testGetDisallowedCharactersInJS ()
	{
		$disallowedChars = ContextSafeness::getDisallowedCharactersInJS();
		$this->assertInternalType('array', $disallowedChars);

		foreach ($disallowedChars as $char)
		{
			$this->assertInternalType('string', $char);
		}
	}
}