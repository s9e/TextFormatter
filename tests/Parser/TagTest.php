<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\Tag
*/
class TagTest extends Test
{
	/**
	* @testdox Mutual invalidation doesn't cause an infinite loop
	*/
	public function testInvalidateNoInfiniteLoop()
	{
		$tag1 = new Tag(Tag::START_TAG, 'x', 0, 0);
		$tag2 = new Tag(Tag::START_TAG, 'x', 0, 0);

		$tag1->cascadeInvalidationTo($tag2);
		$tag2->cascadeInvalidationTo($tag1);

		$tag1->invalidate();
	}
}