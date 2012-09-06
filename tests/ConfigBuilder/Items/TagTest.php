<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Items;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Items\Tag;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Items\Tag
*/
class TagTest extends Test
{
	/**
	* @testdox An array of options can be passed to the constructor
	*/
	public function testConstructorOptions()
	{
		$tag = new Tag(array('nestingLimit' => 123));
		$this->assertSame(123, $tag->nestingLimit);
	}
}