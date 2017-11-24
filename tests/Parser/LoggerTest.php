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
	* @testdox debug() generates a "debug" entry in the logs
	*/
	public function testDebug()
	{
		$logger = new Logger;
		$logger->debug('Hi');

		$this->assertSame(
			[['debug', 'Hi', []]],
			$logger->getLogs()
		);
	}

	/**
	* @testdox err() generates a "err" entry in the logs
	*/
	public function testErr()
	{
		$logger = new Logger;
		$logger->err('Hi');

		$this->assertSame(
			[['err', 'Hi', []]],
			$logger->getLogs()
		);
	}

	/**
	* @testdox info() generates a "info" entry in the logs
	*/
	public function testInfo()
	{
		$logger = new Logger;
		$logger->info('Hi');

		$this->assertSame(
			[['info', 'Hi', []]],
			$logger->getLogs()
		);
	}

	/**
	* @testdox warn() generates a "warn" entry in the logs
	*/
	public function testWarn()
	{
		$logger = new Logger;
		$logger->warn('Hi');

		$this->assertSame(
			[['warn', 'Hi', []]],
			$logger->getLogs()
		);
	}

	/**
	* @testdox The attribute name set by setAttribute() is added to the context if no 'attrName' element is provided
	*/
	public function testContextAttribute()
	{
		$logger = new Logger;

		$logger->setAttribute('foo');
		$logger->debug('Hi');

		$this->assertSame(
			[['debug', 'Hi', ['attrName' => 'foo']]],
			$logger->getLogs()
		);
	}

	/**
	* @testdox The attribute name set by setAttribute() is not added to the context if an 'attrName' element is provided
	*/
	public function testContextAttributePreserved()
	{
		$logger = new Logger;

		$logger->setAttribute('foo');
		$logger->debug('Hi', ['attrName' => 'bar']);

		$this->assertSame(
			[['debug', 'Hi', ['attrName' => 'bar']]],
			$logger->getLogs()
		);
	}

	/**
	* @testdox unsetAttribute() unsets the value stored by setAttribute()
	*/
	public function testUnsetAttribute()
	{
		$logger = new Logger;

		$logger->setAttribute('foo');
		$logger->unsetAttribute();
		$logger->debug('Hi');

		$this->assertSame(
			[['debug', 'Hi', []]],
			$logger->getLogs()
		);
	}

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
			[['debug', 'Hi', ['tag' => $tag]]],
			$logger->getLogs()
		);
	}

	/**
	* @testdox The tag set by setTag() is not added to the context if a 'tag' element is provided
	*/
	public function testContextTagPreserved()
	{
		$logger = new Logger;
		$tag    = new Tag(Tag::SELF_CLOSING_TAG, 'foo', 'FOO', 1, 2);

		$logger->setTag($tag);
		$logger->debug('Hi', ['tag' => 'foo']);

		$this->assertSame(
			[['debug', 'Hi', ['tag' => 'foo']]],
			$logger->getLogs()
		);
	}

	/**
	* @testdox unsetTag() unsets the value stored by setTag()
	*/
	public function testUnsetTag()
	{
		$logger = new Logger;
		$tag    = new Tag(Tag::SELF_CLOSING_TAG, 'foo', 'FOO', 1, 2);

		$logger->setTag($tag);
		$logger->unsetTag();
		$logger->debug('Hi');

		$this->assertSame(
			[['debug', 'Hi', []]],
			$logger->getLogs()
		);
	}

	/**
	* @testdox clear() empties the logs
	*/
	public function testClear()
	{
		$logger = new Logger;
		$logger->debug('Hello');
		$logger->clear();
		$logger->debug('Hi');

		$this->assertSame(
			[['debug', 'Hi', []]],
			$logger->getLogs()
		);
	}
}