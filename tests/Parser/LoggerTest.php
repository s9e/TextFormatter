<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\Logger
*/
class LoggerTest extends Test
{
	/**
	* @testdox The tag set by setTag() is added to the context if no 'tag' element is provided
	*/
	public function testContextTag()
	{
		$logger = new Logger;
		$tag    = new Tag(Tag::SELF_CLOSING_TAG, 'foo', 'FOO', 1, 2);

		$logger->setTag($tag);
		$logger->debug('Hi');

		$this->assertSame(
			array(array('Hi', array('tag' => $tag))),
			$logger->get('debug')
		);
	}
}