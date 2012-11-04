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
		$ct     = new ProgrammableCallback('mt_rand');
		$config = $ct->asConfig();

		$this->assertArrayHasKey('callback', $config);
		$this->assertSame('mt_rand', $config['callback']);
	}

	/**
	* @testdox fromArray() creates an instance from an array
	*/
	public function testFromArray()
	{
		$ct = ProgrammableCallback::fromArray(
			array(
				'callback' => 'mt_rand',
				'params'   => array()
			)
		);

		$this->assertEquals(
			$ct,
			new ProgrammableCallback('mt_rand')
		);
	}

	/**
	* @testdox addParameterByValue() adds a parameter as a value with no name
	*/
	public function testAddParameterByValue()
	{
		$ct = new ProgrammableCallback('strtolower');
		$ct->addParameterByValue('foobar');

		$this->assertEquals(
			array(
				'callback' => 'strtolower',
				'params'   => array('foobar')
			),
			$ct->asConfig()
		);
	}

	/**
	* @testdox addParameterByName() adds a parameter as a name with no value
	*/
	public function testAddParameterByName()
	{
		$ct = new ProgrammableCallback('strtolower');
		$ct->addParameterByName('foobar');

		$this->assertEquals(
			array(
				'callback' => 'strtolower',
				'params'   => array('foobar' => null)
			),
			$ct->asConfig()
		);
	}

	/**
	* @testdox Callback ['foo','bar'] is normalized to 'foo::bar'
	*/
	public function testNormalizeStatic()
	{
		$ct     = new ProgrammableCallback(array(__NAMESPACE__ . '\\DummyStaticCallback', 'bar'));
		$config = $ct->asConfig();

		$this->assertArrayHasKey('callback', $config);
		$this->assertSame(__NAMESPACE__ . '\\DummyStaticCallback::bar', $config['callback']);
	}
}

class DummyStaticCallback
{
	public static function bar()
	{
	}
}