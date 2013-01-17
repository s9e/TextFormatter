<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\ProgrammableCallback
*/
class ProgrammableCallbackTest extends Test
{
	/**
	* @testdox __construct() throws an InvalidArgumentException if its argument is not callable
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage s9e\TextFormatter\Configurator\Items\ProgrammableCallback::__construct() expects a callback
	*/
	public function testInvalidCallback()
	{
		new ProgrammableCallback('*invalid*');
	}

	/**
	* @testdox An array of variables can be set with setVars() or retrieved with getVars()
	*/
	public function testVars()
	{
		$vars = array('foo' => 'bar', 'baz' => 'quux');
		$pc   = new ProgrammableCallback(function($a,$b){});
		$pc->setVars($vars);

		$this->assertSame($vars, $pc->getVars());
	}

	/**
	* @testdox addParameterByValue() adds a parameter as a value with no name
	*/
	public function testAddParameterByValue()
	{
		$pc = new ProgrammableCallback('strtolower');
		$pc->addParameterByValue('foobar');

		$this->assertEquals(
			array(
				'callback' => 'strtolower',
				'params'   => array('foobar')
			),
			$pc->asConfig()
		);
	}

	/**
	* @testdox addParameterByName() adds a parameter as a name with no value
	*/
	public function testAddParameterByName()
	{
		$pc = new ProgrammableCallback('strtolower');
		$pc->addParameterByName('foobar');

		$this->assertEquals(
			array(
				'callback' => 'strtolower',
				'params'   => array('foobar' => null)
			),
			$pc->asConfig()
		);
	}

	/**
	* @testdox Callback ['foo','bar'] is normalized to 'foo::bar'
	*/
	public function testNormalizeStatic()
	{
		$pc     = new ProgrammableCallback(array(__NAMESPACE__ . '\\DummyStaticCallback', 'bar'));
		$config = $pc->asConfig();

		$this->assertArrayHasKey('callback', $config);
		$this->assertSame(__NAMESPACE__ . '\\DummyStaticCallback::bar', $config['callback']);
	}

	/**
	* @testdox getCallback() returns the callback
	*/
	public function testGetCallback()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame('strtolower', $pc->getCallback());
	}

	/**
	* @testdox getJS() returns NULL by default
	*/
	public function testGetJS()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertNull($pc->getJS());
	}

	/**
	* @testdox setJS() accepts a string and normalizes it to an instance of Code
	*/
	public function testSetJSString()
	{
		$js = 'function(str){return str.toLowerCase();}';

		$pc = new ProgrammableCallback('strtolower');
		$pc->setJS($js);

		$this->assertEquals(new Code($js), $pc->getJS());
	}

	/**
	* @testdox setJS() accepts an instance of Code
	*/
	public function testSetJSInstance()
	{
		$js = new Code('function(str){return str.toLowerCase();}');

		$pc = new ProgrammableCallback('strtolower');
		$pc->setJS($js);

		$this->assertSame($js, $pc->getJS());
	}

	/**
	* @testdox asConfig() returns an array containing the callback
	*/
	public function testAsConfig()
	{
		$pc     = new ProgrammableCallback('mt_rand');
		$config = $pc->asConfig();

		$this->assertArrayHasKey('callback', $config);
		$this->assertSame('mt_rand', $config['callback']);
	}

	/**
	* @testdox asConfig() replaces the by-name parameters by the values stored in vars if available
	*/
	public function testAsConfigVars()
	{
		$pc = new ProgrammableCallback('mt_rand');
		$pc->addParameterByName('min');
		$pc->addParameterByValue(55);
		$pc->setVars(array('min' => 5));

		$this->assertSame(
			array(
				'callback' => 'mt_rand',
				'params'   => array(5, 55)
			),
			$pc->asConfig()
		);
	}

	/**
	* @testdox asConfig() returns the callback's JavaScript as a variant if available
	*/
	public function testAsConfigJavaScript()
	{
		$js = new Code('function(str){return str.toLowerCase();}');

		$pc = new ProgrammableCallback('strtolower');
		$pc->setJS($js);

		$config = $pc->asConfig();

		$this->assertArrayHasKey('js', $config);
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Variant',
			$config['js']
		);
		$this->assertSame($js, $config['js']->get('JS'));
	}
}

class DummyStaticCallback
{
	public static function bar()
	{
	}
}