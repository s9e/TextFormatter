<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Helpers\CharacterClassBuilder;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\CharacterClassBuilder
*/
class CharacterClassBuilderTest extends Test
{
	/**
	* @testdox Special characters are properly matched
	*/
	public function testSpecialCharacters()
	{
		$characterClassBuilder            = new CharacterClassBuilder;
		$characterClassBuilder->delimiter = '@';

		$chars  = str_split('!#$()*+-./:<=>?[\\]^{|}', 1);
		$regexp = '@^' . $characterClassBuilder->fromList($chars) . '$@D';
		foreach ($chars as $char)
		{
			$this->assertRegexp($regexp, $char);
		}
	}

	/**
	* @testdox fromList() tests
	* @dataProvider getFromListTests
	*/
	public function testFromList($original, $expected, $delimiter = '/')
	{
		$characterClassBuilder            = new CharacterClassBuilder;
		$characterClassBuilder->delimiter = $delimiter;

		$this->assertSame($expected, $characterClassBuilder->fromList($original));
	}

	public function getFromListTests()
	{
		return [
			[
				['a'],
				'[a]'
			],
			[
				['a', 'b'],
				'[ab]'
			],
			[
				['a', 'b', 'c'],
				'[abc]'
			],
			[
				['a', 'b', 'c', 'd'],
				'[a-d]'
			],
			[
				['a', 'b', 'c', 'd', 'f', 'g', 'h', 'i', 'j'],
				'[a-df-j]'
			],
			[
				['a', 'b', 'c', '\\d'],
				'[\\dabc]'
			],
			[
				['^', 'a', 'b', 'c'],
				'[abc^]'
			],
			[
				['^', '_', '`', 'a'],
				'[\\^-a]'
			],
			[
				[',', '-', '/'],
				'[-,\\/]'
			],
			[
				[',', '-', '.', '/'],
				'[,-\\/]'
			],
			[
				[',', '-', '.', '/'],
				'[,-/]',
				'#'
			],
			[
				['!', '-', '/'],
				'[-!\\/]'
			],
			[
				['*', '+', ',', '-', 'a'],
				'[*--a]'
			],
			[
				['-', '^'],
				'[-^]'
			],
			[
				[']', '^', '_', '`'],
				'[\\]-`]'
			],
			[
				['\\\\', '\\]'],
				'[\\\\\\]]'
			],
			[
				['Z', '[', '\\\\', '\\]'],
				'[Z-\\]]'
			],
			[
				['!', '#', '$'],
				'[!#$]'
			],
			[
				['(', ')', '*'],
				'[()*]'
			],
			[
				['+', '-', '.'],
				'[+-.]'
			],
			[
				['/', ':', '<'],
				'[\\/:<]'
			],
			[
				['=', '>', '?'],
				'[=>?]'
			],
			[
				['[', '\\', ']'],
				'[[\\\\\\]]'
			],
			[
				['^', '{', '|'],
				'[{|^]'
			],
			[
				['{', '|', '}'],
				'[{|}]'
			],
		];
	}
}