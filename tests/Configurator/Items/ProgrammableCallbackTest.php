<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Items\CallbackPlaceholder;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;

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
	* @testdox setJS() sets the callback's Javascript
	*/
	public function testSetJS()
	{
		$js = 'function(str){return str.toLowerCase();}';

		$pc = new ProgrammableCallback('strtolower');
		$pc->setJS($js);

		$this->assertSame($js, $pc->getJS());
	}

	/**
	* @testdox fromArray() creates an instance from an array
	*/
	public function testFromArray()
	{
		$js = 'function(){return 1;}';

		$pc1 = ProgrammableCallback::fromArray(
			array(
				'callback' => 'mt_rand',
				'js'       => $js,
				'params'   => array('min' => null, 55),
				'vars'     => array('foo' => 'bar')
			)
		);

		$pc2 = new ProgrammableCallback('mt_rand');
		$pc2->addParameterByName('min');
		$pc2->addParameterByValue(55);
		$pc2->setJS($js);
		$pc2->setVars(array('foo' => 'bar'));

		$this->assertEquals($pc2, $pc1);
	}

	/**
	* @testdox fromArray() accepts the name of a built-in filter as callback
	*/
	public function testFromArrayBuiltInFilter()
	{
		$pc1 = ProgrammableCallback::fromArray(
			array(
				'callback' => '#range',
				'vars'     => array('min' => 3, 'max' => 5)
			)
		);

		$pc2 = new ProgrammableCallback(new CallbackPlaceholder('#range'));
		$pc2->setVars(array('min' => 3, 'max' => 5));

		$this->assertEquals($pc2, $pc1);
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
	* @testdox If the callback is an instance of CallbackPlaceholder, asConfig() calls the callback's asConfig() and returns its value as callback
	*/
	public function testAsConfigCallbackPlaceholder()
	{
		$pc     = new ProgrammableCallback(new CallbackPlaceholder('#foo'));
		$config = $pc->asConfig();

		$this->assertArrayHasKey('callback', $config);
		$this->assertSame('#foo', $config['callback']);
	}

	/**
	* @testdox asConfig() returns the vars set with setVars() if the callback is an instance of CallbackPlaceholder
	*/
	public function testAsConfigCallbackPlaceholderVars()
	{
		$pc   = new ProgrammableCallback(new CallbackPlaceholder('#foo'));
		$vars = array('foo' => 'bar');
		$pc->setVars($vars);

		$config = $pc->asConfig();

		$this->assertArrayHasKey('vars', $config);
		$this->assertSame($vars, $config['vars']);
	}

	/**
	* @testdox asConfig() returns the callback's Javascript if available
	*/
	public function testAsConfigJavascript()
	{
		$js = 'function(str){return str.toLowerCase();}';

		$pc = new ProgrammableCallback('strtolower');
		$pc->setJS($js);

		$config = $pc->asConfig();

		$this->assertArrayHasKey('js', $config);
		$this->assertSame($js, $config['js']);
	}
}

class DummyStaticCallback
{
	public static function bar()
	{
	}
}