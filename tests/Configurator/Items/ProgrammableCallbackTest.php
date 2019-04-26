<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\ConfigProvider;
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
	*/
	public function testInvalidCallback()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('s9e\\TextFormatter\\Configurator\\Items\\ProgrammableCallback::__construct() expects a callback');

		new ProgrammableCallback('*invalid*');
	}

	/**
	* @testdox An array of variables can be set with setVars() or retrieved with getVars()
	*/
	public function testVars()
	{
		$vars = ['foo' => 'bar', 'baz' => 'quux'];
		$pc   = new ProgrammableCallback(function($a,$b){});
		$pc->setVars($vars);

		$this->assertSame($vars, $pc->getVars());
	}

	/**
	* @testdox setVars() is chainable
	*/
	public function testSetVarsChainable()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->setVars(['foo' => 'bar']));
	}

	/**
	* @testdox A single variable can be set with setVar() without overwriting other variables
	*/
	public function testSetVar()
	{
		$vars = ['foo' => 'bar', 'baz' => 'quux'];
		$pc   = new ProgrammableCallback(function($a,$b){});
		$pc->setVars(['foo' => 'bar']);
		$pc->setVar('baz', 'quux');

		$this->assertSame($vars, $pc->getVars());
	}

	/**
	* @testdox setVars() is chainable
	*/
	public function testSetVarChainable()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->setVar('foo', 'bar'));
	}

	/**
	* @testdox addParameterByValue() adds a parameter as a value with no name
	*/
	public function testAddParameterByValue()
	{
		$pc = new ProgrammableCallback('strtolower');
		$pc->addParameterByValue('foobar');

		$config = $pc->asConfig();

		$this->assertSame(
			['foobar'],
			$config['params']
		);
	}

	/**
	* @testdox addParameterByValue() is chainable
	*/
	public function testAddParameterByValueChainable()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->addParameterByValue('foobar'));
	}

	/**
	* @testdox addParameterByName() adds a parameter as a name with no value
	*/
	public function testAddParameterByName()
	{
		$pc = new ProgrammableCallback('strtolower');
		$pc->addParameterByName('foobar');

		$config = $pc->asConfig();

		$this->assertSame(
			['foobar' => null],
			$config['params']
		);
	}

	/**
	* @testdox addParameterByName() throws an exception when the same parameter is added twice
	*/
	public function testAddParameterByNameDuplicated()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Parameter 'foobar' already exists");

		$pc = new ProgrammableCallback('strtolower');
		$pc->addParameterByName('foobar');
		$pc->addParameterByName('foobar');
	}

	/**
	* @testdox addParameterByName() is chainable
	*/
	public function testAddParameterByNameChainable()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->addParameterByName('foobar'));
	}

	/**
	* @testdox resetParameters() removes all parameters
	*/
	public function testResetParameters()
	{
		$pc = new ProgrammableCallback('mt_rand');
		$pc->addParameterByValue(1);
		$pc->addParameterByValue(2);
		$pc->resetParameters();
		$pc->addParameterByValue(4);
		$pc->addParameterByValue(5);

		$config = $pc->asConfig();

		$this->assertSame(
			[4, 5],
			$config['params']
		);
	}

	/**
	* @testdox resetParameters() is chainable
	*/
	public function testResetParametersChainable()
	{
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->resetParameters());
	}

	/**
	* @testdox Callback '\\strtotime' is normalized to 'strtotime'
	*/
	public function testNormalizeNamespace()
	{
		$pc     = new ProgrammableCallback('\\strtotime');
		$config = $pc->asConfig();

		$this->assertSame('strtotime', $config['callback']);
	}

	/**
	* @testdox Callback ['foo','bar'] is normalized to 'foo::bar'
	*/
	public function testNormalizeStatic()
	{
		$pc     = new ProgrammableCallback([__NAMESPACE__ . '\\DummyStaticCallback', 'bar']);
		$config = $pc->asConfig();

		$this->assertSame(__NAMESPACE__ . '\\DummyStaticCallback::bar', $config['callback']);
	}

	/**
	* @testdox Callback ['\\foo','bar'] is normalized to 'foo::bar'
	*/
	public function testNormalizeStaticNamespace()
	{
		$pc     = new ProgrammableCallback(['\\' . __NAMESPACE__ . '\\DummyStaticCallback', 'bar']);
		$config = $pc->asConfig();

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
	* @testdox getJS() returns 'returnFalse' by default
	*/
	public function testGetJS()
	{
		$pc = new ProgrammableCallback(function(){});

		$this->assertSame('returnFalse', $pc->getJS());
	}

	/**
	* @testdox getJS() returns a JavaScript source for known functions
	*/
	public function testGetJSAutofills()
	{
		$pc = new ProgrammableCallback('strtolower');
		$this->assertSame("function(str)\n{\n\treturn str.toLowerCase();\n}", $pc->getJS());
	}

	/**
	* @testdox getJS() returns NULL if no JS was set and the callback is a function that is not found in Configurator/JavaScript/functions/
	*/
	public function testGetJSNoAutofill()
	{
		$pc = new ProgrammableCallback('levenshtein');

		$this->assertSame('returnFalse', $pc->getJS());
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
	* @testdox setJS() is chainable
	*/
	public function testSetJSChainable()
	{
		$js = 'function(str){return str.toLowerCase();}';
		$pc = new ProgrammableCallback('strtolower');

		$this->assertSame($pc, $pc->setJS($js));
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
		$pc->setVars(['min' => 5]);

		$config = $pc->asConfig();

		$this->assertSame(
			[5, 55],
			$config['params']
		);
	}

	/**
	* @testdox asConfig() returns the callback's JavaScript as a Code object if available
	*/
	public function testAsConfigJavaScript()
	{
		$js = 'function(){return "";}';

		$pc = new ProgrammableCallback(function(){});
		$pc->setJS($js);

		$config = $pc->asConfig();

		$this->assertArrayHasKey('js', $config);
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\JavaScript\\Code',
			$config['js']
		);
		$this->assertEquals($js, $config['js']);
	}

	/**
	* @testdox asConfig() replaces values that implement ConfigProvider with their config value
	*/
	public function testAsConfigProvider()
	{
		$pc = new ProgrammableCallback(function(){});
		$pc->setVars(['x' => new DummyConfigProvider]);

		$pc->addParameterByName('x');
		$pc->addParameterByValue(new DummyConfigProvider);

		$config = $pc->asConfig();

		$this->assertSame('foo', $config['params'][0]);
		$this->assertSame('foo', $config['params'][1]);
	}

	/**
	* @testdox asConfig() recurses into params via ConfigHelper::toArray() to convert structures to arrays
	*/
	public function testAsConfigProviderDeep()
	{
		$pc = new ProgrammableCallback(function(){});
		$pc->addParameterByValue([new DummyConfigProvider]);

		$config = $pc->asConfig();

		$this->assertSame('foo', $config['params'][0][0]);
	}

	/**
	* @testdox asConfig() preserves NULL values and empty arrays in the callback's parameters
	*/
	public function testAsConfigPreserve()
	{
		$pc = new ProgrammableCallback(function(){});
		$pc->addParameterByValue(null);
		$pc->addParameterByValue([]);

		$config = $pc->asConfig();

		$this->assertSame(
			[null, []],
			$config['params']
		);
	}
}

class DummyStaticCallback
{
	public static function bar()
	{
	}
}

class DummyConfigProvider implements ConfigProvider
{
	public function asConfig()
	{
		return 'foo';
	}
}