<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Utils;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Utils
*/
class UtilsTest extends Test
{
	/**
	* @testdox encodeUnicodeSupplementaryCharacters() tests
	* @dataProvider getEncodeUnicodeSupplementaryCharactersTests
	*/
	public function testEncodeUnicodeSupplementaryCharacters($original, $expected)
	{
		$this->assertSame($expected, Utils::encodeUnicodeSupplementaryCharacters($original));
	}

	public function getEncodeUnicodeSupplementaryCharactersTests()
	{
		return [
			[
				'😀😁',
				'&#128512;&#128513;'
			],
		];
	}
}