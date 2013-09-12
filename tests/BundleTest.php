<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Bundle;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Bundle
*/
class BundleTest extends Test
{
	public function setUp()
	{
		DummyBundle::_reset($this);
	}

	/**
	* @testdox parse() creates a parser, parses the input and returns the output
	*/
	public function testParse()
	{
		$text = 'Hello world';
		$xml  = '<pt>Hello world</pt>';

		$mock = $this->getMock('stdClass', ['parse']);
		$mock->expects($this->once())
		     ->method('parse')
		     ->with($text)
		     ->will($this->returnValue($xml));

		DummyBundle::$_parser = $mock;

		$this->assertSame($xml, DummyBundle::parse($text));
		$this->assertSame(DummyBundle::$calls, ['getParser' => 1, 'getRenderer' => 0]);
	}

	/**
	* @testdox parse() reuses the same parser on consecutive calls
	*/
	public function testParseSingleton()
	{
		$text1 = 'Hello world';
		$xml1  = '<pt>Hello world</pt>';

		$text2 = 'Sup Earth :)';
		$xml2  = '<rt>Sup Earth <E>:)</e></rt>';

		$mock = $this->getMock('stdClass', ['parse']);
		$mock->expects($this->at(0))
		     ->method('parse')
		     ->with($text1)
		     ->will($this->returnValue($xml1));
		$mock->expects($this->at(1))
		     ->method('parse')
		     ->with($text2)
		     ->will($this->returnValue($xml2));

		DummyBundle::$_parser = $mock;

		$this->assertSame($xml1, DummyBundle::parse($text1));
		$this->assertSame($xml2, DummyBundle::parse($text2));
		$this->assertSame(DummyBundle::$calls, ['getParser' => 1, 'getRenderer' => 0]);
	}

	/**
	* @testdox render() creates a renderer, renders the input and returns the result
	*/
	public function testRender()
	{
		$xml  = '<pt>Hello world</pt>';
		$html = 'Hello world';

		$mock = $this->getMock('stdClass', ['render', 'setParameters']);
		$mock->expects($this->once())
		     ->method('render')
		     ->with($xml)
		     ->will($this->returnValue($html));

		DummyBundle::$_renderer = $mock;

		$this->assertSame($html, DummyBundle::render($xml));
		$this->assertSame(DummyBundle::$calls, ['getParser' => 0, 'getRenderer' => 1]);
	}

	/**
	* @testdox render() reuses the same renderer on consecutive calls
	*/
	public function testRenderSingleton()
	{
		$xml  = '<pt>Hello world</pt>';
		$html = 'Hello world';

		$mock = $this->getMock('stdClass', ['render', 'setParameters']);
		$mock->expects($this->exactly(2))
		     ->method('render')
		     ->with($xml)
		     ->will($this->returnValue($html));

		DummyBundle::$_renderer = $mock;

		$this->assertSame($html, DummyBundle::render($xml));
		$this->assertSame($html, DummyBundle::render($xml));
		$this->assertSame(DummyBundle::$calls, ['getParser' => 0, 'getRenderer' => 1]);
	}

	/**
	* @testdox render() calls setParameters() with its second argument
	*/
	public function testRenderParameters()
	{
		$xml    = '<pt>Hello world</pt>';
		$html   = 'Hello world';
		$params = ['foo' => 'bar'];

		$mock = $this->getMock('stdClass', ['render', 'setParameters']);
		$mock->expects($this->once())
		     ->method('render')
		     ->with($xml)
		     ->will($this->returnValue($html));
		$mock->expects($this->once())
		     ->method('setParameters')
		     ->with($params);

		DummyBundle::$_renderer = $mock;

		$this->assertSame($html, DummyBundle::render($xml, $params));
		$this->assertSame(DummyBundle::$calls, ['getParser' => 0, 'getRenderer' => 1]);
	}

	/**
	* @testdox renderMulti() creates a renderer, renders the input and returns the result
	*/
	public function testRenderMulti()
	{
		$xml  = '<pt>Hello world</pt>';
		$html = 'Hello world';

		$mock = $this->getMock('stdClass', ['renderMulti', 'setParameters']);
		$mock->expects($this->once())
		     ->method('renderMulti')
		     ->with([$xml])
		     ->will($this->returnValue([$html]));

		DummyBundle::$_renderer = $mock;

		$this->assertSame([$html], DummyBundle::renderMulti([$xml]));
		$this->assertSame(DummyBundle::$calls, ['getParser' => 0, 'getRenderer' => 1]);
	}

	/**
	* @testdox renderMulti() reuses the same renderer on consecutive calls
	*/
	public function testRenderMultiSingleton()
	{
		$xml  = '<pt>Hello world</pt>';
		$html = 'Hello world';

		$mock = $this->getMock('stdClass', ['renderMulti', 'setParameters']);
		$mock->expects($this->exactly(2))
		     ->method('renderMulti')
		     ->with([$xml])
		     ->will($this->returnValue([$html]));

		DummyBundle::$_renderer = $mock;

		$this->assertSame([$html], DummyBundle::renderMulti([$xml]));
		$this->assertSame([$html], DummyBundle::renderMulti([$xml]));
		$this->assertSame(DummyBundle::$calls, ['getParser' => 0, 'getRenderer' => 1]);
	}

	/**
	* @testdox renderMulti() calls setParameters() with its second argument
	*/
	public function testRenderMultiParameters()
	{
		$xml    = '<pt>Hello world</pt>';
		$html   = 'Hello world';
		$params = ['foo' => 'bar'];

		$mock = $this->getMock('stdClass', ['renderMulti', 'setParameters']);
		$mock->expects($this->once())
		     ->method('renderMulti')
		     ->with([$xml])
		     ->will($this->returnValue([$html]));
		$mock->expects($this->once())
		     ->method('setParameters')
		     ->with($params);

		DummyBundle::$_renderer = $mock;

		$this->assertSame([$html], DummyBundle::renderMulti([$xml], $params));
		$this->assertSame(DummyBundle::$calls, ['getParser' => 0, 'getRenderer' => 1]);
	}

	/**
	* @testdox unparse() takes the XML representation and returns the original text
	*/
	public function testUnparse()
	{
		$this->assertSame('Hello', DummyBundle::unparse('<pt>Hello</pt>'));
	}

	/**
	* @testdox reset() removes the cached parser and renderer
	*/
	public function testReset()
	{
		DummyBundle::$parser   = DummyBundle::getParser();
		DummyBundle::$renderer = DummyBundle::getRenderer();

		$this->assertTrue(isset(DummyBundle::$parser));
		$this->assertTrue(isset(DummyBundle::$renderer));

		DummyBundle::reset();

		$this->assertFalse(isset(DummyBundle::$parser));
		$this->assertFalse(isset(DummyBundle::$renderer));
	}
}

class DummyBundle extends Bundle
{
	public static $calls = ['getParser' => 0, 'getRenderer' => 0];
	public static $_parser;
	public static $_renderer;
	public static $parser;
	public static $renderer;

	public static function _reset(Test $test)
	{
		self::$calls = ['getParser' => 0, 'getRenderer' => 0];

		$mock = $test->getMock('stdClass', ['parse', 'render', 'renderMulti', 'setParameters']);
		$mock->expects($test->never())->method('parse');
		$mock->expects($test->never())->method('render');
		$mock->expects($test->never())->method('renderMulti');
		$mock->expects($test->never())->method('setParameters');

		static::$parser    = null;
		static::$_parser   = $mock;
		static::$renderer  = null;
		static::$_renderer = $mock;
	}

	public static function getParser()
	{
		++self::$calls['getParser'];

		return self::$_parser;
	}

	public static function getRenderer()
	{
		++self::$calls['getRenderer'];

		return self::$_renderer;
	}
}