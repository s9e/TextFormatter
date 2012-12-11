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
			array(array('debug', 'Hi', array())),
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
			array(array('err', 'Hi', array())),
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
			array(array('info', 'Hi', array())),
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
			array(array('warn', 'Hi', array())),
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
			array(array('debug', 'Hi', array('attrName' => 'foo'))),
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
		$logger->debug('Hi', array('attrName' => 'bar'));

		$this->assertSame(
			array(array('debug', 'Hi', array('attrName' => 'bar'))),
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
			array(array('debug', 'Hi', array())),
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
			array(array('debug', 'Hi', array('tag' => $tag))),
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
		$logger->debug('Hi', array('tag' => 'foo'));

		$this->assertSame(
			array(array('debug', 'Hi', array('tag' => 'foo'))),
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
			array(array('debug', 'Hi', array())),
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
			array(array('debug', 'Hi', array())),
			$logger->get()
		);
	}
}