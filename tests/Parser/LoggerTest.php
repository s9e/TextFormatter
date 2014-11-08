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
			$logger->get()
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
			$logger->get()
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
			$logger->get()
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
			$logger->get()
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
			$logger->get()
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
			$logger->get()
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
			$logger->get()
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
			$logger->get()
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
			$logger->get()
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
			$logger->get()
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
			$logger->get()
		);
	}

	/**
	* @testdox on() attaches a callback to be executed when the corresponding log type is used
	*/
	public function testOn()
	{
		$mock = $this->getMock('stdClass', ['foo']);
		$mock->expects($this->once())
		     ->method('foo');

		$logger = new Logger;
		$logger->on('err', [$mock, 'foo']);
		$logger->err('hi');
	}

	/**
	* @testdox on() throws an exception on invalid callback
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage on() expects a valid callback
	*/
	public function testOnInvalid()
	{
		$logger = new Logger;
		$logger->on('err', '*invalid*');
	}

	/**
	* @testdox on() callbacks receive the log message and its context
	*/
	public function testOnArguments()
	{
		$mock = $this->getMock('stdClass', ['foo']);
		$mock->expects($this->once())
		     ->method('foo')
		     ->with('hi', ['x' => 'y']);

		$logger = new Logger;
		$logger->on('err', [$mock, 'foo']);
		$logger->err('hi', ['x' => 'y']);
	}

	/**
	* @testdox on() callbacks can modify the log message and its context if their signature accepts them as a reference
	*/
	public function testOnArgumentsByReference()
	{
		$logger = new Logger;
		$logger->on(
			'err',
			function (&$msg, &$context)
			{
				$msg     = 'foo';
				$context = ['bar' => 'baz'];
			}
		);

		$logger->err('hi', ['x' => 'y']);

		$this->assertSame(
			[['err', 'foo', ['bar' => 'baz']]],
			$logger->get()
		);
	}

	/**
	* @testdox on() callbacks are only executed for the log type they were registered for
	*/
	public function testOnLogType()
	{
		$mock = $this->getMock('stdClass', ['debug', 'err', 'warn']);
		$mock->expects($this->once())
		     ->method('err');
		$mock->expects($this->once())
		     ->method('warn');
		$mock->expects($this->never())
		     ->method('debug');

		$logger = new Logger;
		$logger->on('err',  [$mock, 'err']);
		$logger->on('warn', [$mock, 'warn']);
		$logger->on('debug', [$mock, 'debug']);

		$logger->err('hi');
		$logger->warn('hi');
	}
}