<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Bundle;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Bundle
*/
class BundleTest extends Test
{
	protected function setUp(): void
	{
		DummyBundle::_reset($this);
	}

	/**
	* @testdox The default implementation for getJS() returns an empty string
	*/
	public function testGetJS()
	{
		$this->assertSame('', DummyBundle::getJS());
	}

	/**
	* @testdox parse() creates a parser, parses the input and returns the output
	*/
	public function testParse()
	{
		$text = 'Hello world';
		$xml  = '<t>Hello world</t>';

		$mock = $this->getMockBuilder('stdClass')->addMethods(['parse'])->getMock();
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

		$mock = $this->getMockBuilder('stdClass')->addMethods(['parse'])->getMock();
		$mock->expects($this->exactly(2))
		     ->method('parse')
		     ->willReturnOnConsecutiveCalls($xml1, $xml2);

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
		$mock = $this->getMockBuilder('stdClass')->addMethods(['parse'])->getMock();
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
		$mock = $this->getMockBuilder('stdClass')->addMethods(['parse'])->getMock();
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

		$mock = $this->getMockBuilder('stdClass')->addMethods(['render', 'setParameters'])->getMock();
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

		$mock = $this->getMockBuilder('stdClass')->addMethods(['render', 'setParameters'])->getMock();
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

		$mock = $this->getMockBuilder('stdClass')->addMethods(['render', 'setParameters'])->getMock();
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
		$mock = $this->getMockBuilder('stdClass')->addMethods(['render'])->getMock();
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
		$mock = $this->getMockBuilder('stdClass')->addMethods(['render'])->getMock();
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

		$mock = $test->getMockBuilder('stdClass')
		             ->addMethods(['parse', 'render', 'setParameters'])
		             ->getMock();
		$mock->expects($test->never())->method('parse');
		$mock->expects($test->never())->method('render');
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