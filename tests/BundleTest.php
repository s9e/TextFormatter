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

		$mock = $this->getMock('stdClass', array('parse'));
		$mock->expects($this->once())
		     ->method('parse')
		     ->with($text)
		     ->will($this->returnValue($xml));

		DummyBundle::$_parser = $mock;

		$this->assertSame($xml, DummyBundle::parse($text));
		$this->assertSame(DummyBundle::$calls, array('getParser' => 1, 'getRenderer' => 0));
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

		$mock = $this->getMock('stdClass', array('parse'));
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
		$this->assertSame(DummyBundle::$calls, array('getParser' => 1, 'getRenderer' => 0));
	}

	/**
	* @testdox parse() executes static::$beforeParse before calling the parser's parse() method
	*/
	public function testBeforeParse()
	{
		$_this = $this;

		$mock = $this->getMock('stdClass', array('parse'));
		$mock->expects($this->once())
		     ->method('parse')
		     ->with('beforeParse')
		     ->will($this->returnValue('<t></t>'));
		DummyBundle::$_parser = $mock;

		DummyBundle::$beforeParse = function ($arg) use ($_this)
		{
			$_this->assertSame('', $arg);

			return 'beforeParse';
		};

		$this->assertSame('<t></t>', DummyBundle::parse(''));
	}

	/**
	* @testdox parse() executes static::$afterParse after calling the parser's parse() method
	*/
	public function testAfterParse()
	{
		$_this = $this;

		$mock = $this->getMock('stdClass', array('parse'));
		$mock->expects($this->once())
		     ->method('parse')
		     ->with('')
		     ->will($this->returnValue('<t></t>'));
		DummyBundle::$_parser = $mock;

		DummyBundle::$afterParse = function ($arg) use ($_this)
		{
			$_this->assertSame('<t></t>', $arg);

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

		$mock = $this->getMock('stdClass', array('render', 'setParameters'));
		$mock->expects($this->once())
		     ->method('render')
		     ->with($xml)
		     ->will($this->returnValue($html));

		DummyBundle::$_renderer = $mock;

		$this->assertSame($html, DummyBundle::render($xml));
		$this->assertSame(DummyBundle::$calls, array('getParser' => 0, 'getRenderer' => 1));
	}

	/**
	* @testdox render() reuses the same renderer on consecutive calls
	*/
	public function testRenderSingleton()
	{
		$xml  = '<t>Hello world</t>';
		$html = 'Hello world';

		$mock = $this->getMock('stdClass', array('render', 'setParameters'));
		$mock->expects($this->exactly(2))
		     ->method('render')
		     ->with($xml)
		     ->will($this->returnValue($html));

		DummyBundle::$_renderer = $mock;

		$this->assertSame($html, DummyBundle::render($xml));
		$this->assertSame($html, DummyBundle::render($xml));
		$this->assertSame(DummyBundle::$calls, array('getParser' => 0, 'getRenderer' => 1));
	}

	/**
	* @testdox render() calls setParameters() with its second argument
	*/
	public function testRenderParameters()
	{
		$xml    = '<t>Hello world</t>';
		$html   = 'Hello world';
		$params = array('foo' => 'bar');

		$mock = $this->getMock('stdClass', array('render', 'setParameters'));
		$mock->expects($this->once())
		     ->method('render')
		     ->with($xml)
		     ->will($this->returnValue($html));
		$mock->expects($this->once())
		     ->method('setParameters')
		     ->with($params);

		DummyBundle::$_renderer = $mock;

		$this->assertSame($html, DummyBundle::render($xml, $params));
		$this->assertSame(DummyBundle::$calls, array('getParser' => 0, 'getRenderer' => 1));
	}

	/**
	* @testdox render() executes static::$beforeRender before calling the renderer's render() method
	*/
	public function testBeforeRender()
	{
		$_this = $this;

		$mock = $this->getMock('stdClass', array('render'));
		$mock->expects($this->once())
		     ->method('render')
		     ->with('<t>beforeRender</t>')
		     ->will($this->returnValue('...'));
		DummyBundle::$_renderer = $mock;

		DummyBundle::$beforeRender = function ($arg) use ($_this)
		{
			$_this->assertSame('<t></t>', $arg);

			return '<t>beforeRender</t>';
		};

		$this->assertSame('...', DummyBundle::render('<t></t>'));
	}

	/**
	* @testdox render() executes static::$afterRender after calling the renderer's render() method
	*/
	public function testAfterRender()
	{
		$_this = $this;

		$mock = $this->getMock('stdClass', array('render'));
		$mock->expects($this->once())
		     ->method('render')
		     ->with('')
		     ->will($this->returnValue('...'));
		DummyBundle::$_renderer = $mock;

		DummyBundle::$afterRender = function ($arg) use ($_this)
		{
			$_this->assertSame('...', $arg);

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

		$mock = $this->getMock('stdClass', array('renderMulti', 'setParameters'));
		$mock->expects($this->once())
		     ->method('renderMulti')
		     ->with(array($xml))
		     ->will($this->returnValue(array($html)));

		DummyBundle::$_renderer = $mock;

		$this->assertSame(array($html), DummyBundle::renderMulti(array($xml)));
		$this->assertSame(DummyBundle::$calls, array('getParser' => 0, 'getRenderer' => 1));
	}

	/**
	* @testdox renderMulti() reuses the same renderer on consecutive calls
	*/
	public function testRenderMultiSingleton()
	{
		$xml  = '<t>Hello world</t>';
		$html = 'Hello world';

		$mock = $this->getMock('stdClass', array('renderMulti', 'setParameters'));
		$mock->expects($this->exactly(2))
		     ->method('renderMulti')
		     ->with(array($xml))
		     ->will($this->returnValue(array($html)));

		DummyBundle::$_renderer = $mock;

		$this->assertSame(array($html), DummyBundle::renderMulti(array($xml)));
		$this->assertSame(array($html), DummyBundle::renderMulti(array($xml)));
		$this->assertSame(DummyBundle::$calls, array('getParser' => 0, 'getRenderer' => 1));
	}

	/**
	* @testdox renderMulti() calls setParameters() with its second argument
	*/
	public function testRenderMultiParameters()
	{
		$xml    = '<t>Hello world</t>';
		$html   = 'Hello world';
		$params = array('foo' => 'bar');

		$mock = $this->getMock('stdClass', array('renderMulti', 'setParameters'));
		$mock->expects($this->once())
		     ->method('renderMulti')
		     ->with(array($xml))
		     ->will($this->returnValue(array($html)));
		$mock->expects($this->once())
		     ->method('setParameters')
		     ->with($params);

		DummyBundle::$_renderer = $mock;

		$this->assertSame(array($html), DummyBundle::renderMulti(array($xml), $params));
		$this->assertSame(DummyBundle::$calls, array('getParser' => 0, 'getRenderer' => 1));
	}

	/**
	* @testdox renderMulti() executes static::$beforeRender on every entry before calling the renderer's renderMulti() method
	*/
	public function testBeforeRenderMulti()
	{
		$mock = $this->getMock('stdClass', array('renderMulti'));
		$mock->expects($this->once())
		     ->method('renderMulti')
		     ->with(array('<t>beforeRender0</t>', '<t>beforeRender1</t>'))
		     ->will($this->returnValue(array('x0x', 'x1x')));
		DummyBundle::$_renderer = $mock;

		$mock = $this->getMock('stdClass', array('foo'));
		$mock->expects($this->at(0))
		     ->method('foo')
		     ->with('<t>0</t>')
		     ->will($this->returnValue('<t>beforeRender0</t>'));
		$mock->expects($this->at(1))
		     ->method('foo')
		     ->with('<t>1</t>')
		     ->will($this->returnValue('<t>beforeRender1</t>'));
		DummyBundle::$beforeRender = array($mock, 'foo');

		$this->assertSame(
			array(
				'x0x',
				'x1x'
			),
			DummyBundle::renderMulti(array(
				'<t>0</t>',
				'<t>1</t>'
			))
		);
	}

	/**
	* @testdox renderMulti() executes static::$afterRender on every entry after calling the renderer's renderMulti() method
	*/
	public function testAfterRenderMulti()
	{
		$mock = $this->getMock('stdClass', array('renderMulti'));
		$mock->expects($this->once())
		     ->method('renderMulti')
		     ->with(array('<t>0</t>', '<t>1</t>'))
		     ->will($this->returnValue(array('x0x', 'x1x')));
		DummyBundle::$_renderer = $mock;

		$mock = $this->getMock('stdClass', array('foo'));
		$mock->expects($this->at(0))
		     ->method('foo')
		     ->with('x0x')
		     ->will($this->returnValue('afterRender0'));
		$mock->expects($this->at(1))
		     ->method('foo')
		     ->with('x1x')
		     ->will($this->returnValue('afterRender1'));
		DummyBundle::$afterRender = array($mock, 'foo');

		$this->assertSame(
			array(
				'afterRender0',
				'afterRender1'
			),
			DummyBundle::renderMulti(array(
				'<t>0</t>',
				'<t>1</t>'
			))
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
		$_this = $this;

		DummyBundle::$beforeUnparse = function ($arg) use ($_this)
		{
			$_this->assertSame('<t>original</t>', $arg);

			return '<t>beforeUnparse</t>';
		};

		$this->assertSame('beforeUnparse', DummyBundle::unparse('<t>original</t>'));
	}

	/**
	* @testdox unparse() executes static::$afterUnparse after calling the parser's unparse() method
	*/
	public function testAfterUnparse()
	{
		$_this = $this;

		DummyBundle::$afterUnparse = function ($arg) use ($_this)
		{
			$_this->assertSame('original', $arg);

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
	public static $calls = array('getParser' => 0, 'getRenderer' => 0);
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
		self::$calls = array('getParser' => 0, 'getRenderer' => 0);

		$mock = $test->getMock('stdClass', array('parse', 'render', 'renderMulti', 'setParameters'));
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