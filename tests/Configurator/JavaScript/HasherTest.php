<?php

namespace s9e\TextFormatter\Tests\Configurator\JavaScript;

use s9e\TextFormatter\Configurator\JavaScript\Hasher;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\JavaScript\Hasher
*/
class HasherTest extends Test
{
	/**
	* @testdox quickHash() matches hash() output
	* @dataProvider getQuickHashTests
	*/
	public function testQuickHash($expected, $text)
	{
		$this->assertSame($expected, Hasher::quickHash($text));
	}

	public static function getQuickHashTests()
	{
		// These values were computed manually in node.js hash() from render.js
		//
		// > hash("test string")
		// 450823287
		// > hash("\u{1F600}")
		// -1807763906
		// > hash("\u2600")
		// 637543936
		// > hash("\u00A9")
		// 11075753
		return [
			[450823287,   'test string'],
			[-1807763906, "\u{1F600}"  ],
			[637543936,   "\u{2600}"   ],
			[11075753,    "\u{00A9}"   ]
		];
	}

	/**
	* @testdox quickHash() throws an exception on invalid UTF-8
	*/
	public function testQuickHashInvalid()
	{
		$this->expectException('ValueError');
		Hasher::quickHash("\xFF");
	}
}