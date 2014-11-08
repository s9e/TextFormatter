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
		$xml  = '<t>Hello world</t>';

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
		$xml1  = '<t>Hello world</t>';

		$text2 = 'Sup Earth :)';
		$xml2  = '<r>Sup Earth <E>:)</e></r>';

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
	* @testdox parse() executes static::$beforeParse before calling the parser's parse() method
	*/
	public function testBeforeParse()
	{
		$mock = $this->getMock('stdClass', ['parse']);
		$mock->expects($this->once())
		     ->method('parse')
		     ->with('beforeParse')
		     ->will($this->returnValue('<t></t>'));
		DummyBundle::$_parser = $mock;

		DummyBundle::$beforeParse = function ($arg)
		{
			$this->assertSame('', $arg);

			return 'beforeParse';
		};

		$this->assertSame('<t></t>', DummyBundle::parse(''));
	}

	/**
	* @testdox parse() executes static::$afterParse after calling the parser's parse() method
	*/
	public function testAfterParse()
	{
		$mock = $this->getMock('stdClass', ['parse']);
		$mock->expects($this->once())
		     ->method('parse')
		     ->with('')
		     ->will($this->returnValue('<t></t>'));
		DummyBundle::$_parser = $mock;

		DummyBundle::$afterParse = function ($arg)
		{
			$this->assertSame('<t></t>', $arg);

			return '<t>afterParse</t>';
		};

		$this->assertSame('<t>afterParse</t>', DummyBundle::parse(''));
	}

	/**
	* @testdox render() creates a renderer, renders the input and returns the result
	*/
	public function testRender()
	{
		$xml  = '<t>Hello world</t>';
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
		$xml  = '<t>Hello world</t>';
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
		$xml    = '<t>Hello world</t>';
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
	* @testdox render() executes static::$beforeRender before calling the renderer's render() method
	*/
	public function testBeforeRender()
	{
		$mock = $this->getMock('stdClass', ['render']);
		$mock->expects($this->once())
		     ->method('render')
		     ->with('<t>beforeRender</t>')
		     ->will($this->returnValue('...'));
		DummyBundle::$_renderer = $mock;

		DummyBundle::$beforeRender = function ($arg)
		{
			$this->assertSame('<t></t>', $arg);

			return '<t>beforeRender</t>';
		};

		$this->assertSame('...', DummyBundle::render('<t></t>'));
	}

	/**
	* @testdox render() executes static::$afterRender after calling the renderer's render() method
	*/
	public function testAfterRender()
	{
		$mock = $this->getMock('stdClass', ['render']);
		$mock->expects($this->once())
		     ->method('render')
		     ->with('')
		     ->will($this->returnValue('...'));
		DummyBundle::$_renderer = $mock;

		DummyBundle::$afterRender = function ($arg)
		{
			$this->assertSame('...', $arg);

			return 'afterRender';
		};

		$this->assertSame('afterRender', DummyBundle::render(''));
	}

	/**
	* @testdox renderMulti() creates a renderer, renders the input and returns the result
	*/
	public function testRenderMulti()
	{
		$xml  = '<t>Hello world</t>';
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
		$xml  = '<t>Hello world</t>';
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
		$xml    = '<t>Hello world</t>';
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
	* @testdox renderMulti() executes static::$beforeRender on every entry before calling the renderer's renderMulti() method
	*/
	public function testBeforeRenderMulti()
	{
		$mock = $this->getMock('stdClass', ['renderMulti']);
		$mock->expects($this->once())
		     ->method('renderMulti')
		     ->with(['<t>beforeRender0</t>', '<t>beforeRender1</t>'])
		     ->will($this->returnValue(['x0x', 'x1x']));
		DummyBundle::$_renderer = $mock;

		$mock = $this->getMock('stdClass', ['foo']);
		$mock->expects($this->at(0))
		     ->method('foo')
		     ->with('<t>0</t>')
		     ->will($this->returnValue('<t>beforeRender0</t>'));
		$mock->expects($this->at(1))
		     ->method('foo')
		     ->with('<t>1</t>')
		     ->will($this->returnValue('<t>beforeRender1</t>'));
		DummyBundle::$beforeRender = [$mock, 'foo'];

		$this->assertSame(
			[
				'x0x',
				'x1x'
			],
			DummyBundle::renderMulti([
				'<t>0</t>',
				'<t>1</t>'
			])
		);
	}

	/**
	* @testdox renderMulti() executes static::$afterRender on every entry after calling the renderer's renderMulti() method
	*/
	public function testAfterRenderMulti()
	{
		$mock = $this->getMock('stdClass', ['renderMulti']);
		$mock->expects($this->once())
		     ->method('renderMulti')
		     ->with(['<t>0</t>', '<t>1</t>'])
		     ->will($this->returnValue(['x0x', 'x1x']));
		DummyBundle::$_renderer = $mock;

		$mock = $this->getMock('stdClass', ['foo']);
		$mock->expects($this->at(0))
		     ->method('foo')
		     ->with('x0x')
		     ->will($this->returnValue('afterRender0'));
		$mock->expects($this->at(1))
		     ->method('foo')
		     ->with('x1x')
		     ->will($this->returnValue('afterRender1'));
		DummyBundle::$afterRender = [$mock, 'foo'];

		$this->assertSame(
			[
				'afterRender0',
				'afterRender1'
			],
			DummyBundle::renderMulti([
				'<t>0</t>',
				'<t>1</t>'
			])
		);
	}

	/**
	* @testdox unparse() takes the XML representation and returns the original text
	*/
	public function testUnparse()
	{
		$this->assertSame('Hello', DummyBundle::unparse('<t>Hello</t>'));
	}

	/**
	* @testdox unparse() executes static::$beforeUnparse before calling the parser's unparse() method
	*/
	public function testBeforeUnparse()
	{
		DummyBundle::$beforeUnparse = function ($arg)
		{
			$this->assertSame('<t>original</t>', $arg);

			return '<t>beforeUnparse</t>';
		};

		$this->assertSame('beforeUnparse', DummyBundle::unparse('<t>original</t>'));
	}

	/**
	* @testdox unparse() executes static::$afterUnparse after calling the parser's unparse() method
	*/
	public function testAfterUnparse()
	{
		DummyBundle::$afterUnparse = function ($arg)
		{
			$this->assertSame('original', $arg);

			return 'afterUnparse';
		};

		$this->assertSame('afterUnparse', DummyBundle::unparse('<t>original</t>'));
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
	public static $lastEvent;

	public static $beforeParse;
	public static $afterParse;
	public static $beforeRender;
	public static $afterRender;
	public static $beforeUnparse;
	public static $afterUnparse;

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

		static::$beforeParse   = null;
		static::$afterParse    = null;
		static::$beforeRender  = null;
		static::$afterRender   = null;
		static::$beforeUnparse = null;
		static::$afterUnparse  = null;
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