<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Tests\Test;
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
	* @testdox fromArray() creates an instance from an array
	*/
	public function testFromArray()
	{
		$pc = ProgrammableCallback::fromArray(
			array(
				'callback' => 'mt_rand',
				'params'   => array()
			)
		);

		$this->assertEquals(
			$pc,
			new ProgrammableCallback('mt_rand')
		);
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
}

class DummyStaticCallback
{
	public static function bar()
	{
	}
}