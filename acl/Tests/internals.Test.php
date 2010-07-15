<?php

namespace s9e\toolkit\acl;

include_once __DIR__ . '/../builder.php';
include_once __DIR__ . '/../reader.php';

class testInternals extends \PHPUnit_Framework_TestCase
{
	public function testIdenticalPermsAreOptimizedAway()
	{
		$builder = new builder;
		$builder->allow('foo', array('scope' => 123));
		$builder->allow('bar', array('scope' => 123));

		$config = $builder->getReaderConfig();

		$this->assertSame(
			$config['foo'][reader::KEY_PERMS]['foo'],
			$config['bar'][reader::KEY_PERMS]['foo']
		);
	}

	public function testIdenticalScopesAreOptimizedAway()
	{
		$builder = new builder;
		$builder->allow('foo', array('scope' => 123));
		$builder->allow('foo', array('scope' => 456));

		$config = $builder->getReaderConfig();

		$this->assertSame(
			$config['foo'][reader::KEY_SCOPES]['scope'][123],
			$config['foo'][reader::KEY_SCOPES]['scope'][456]
		);
	}

	public function testUselessPermsAreOptimizedAway()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->deny('bar');

		$config = $builder->getReaderConfig();

		$this->assertFalse(isset($config['bar']));
	}

	public function testUselessScopesAreOptimizedAway()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->allow('foo', array('scope' => 123));
		$builder->allow('bar', array('scope' => 456));

		$config = $builder->getReaderConfig();

		$this->assertFalse(isset($config['foo'][reader::KEY_SCOPES]['scope'][123]));
		$this->assertTrue(isset($config['foo'][reader::KEY_SCOPES]['scope'][456]));
	}

	/**
	* @dataProvider getMasks
	*/
	public function testMergeMasks($masks, $expected, $msg = null)
	{
		$method = new \ReflectionMethod('s9e\\toolkit\\acl\\builder', 'mergeMasks');
		$method->setAccessible(true);

		$this->assertSame(
			$expected,
			$method->invokeArgs(null, array($masks)),
			$msg
		);
	}

	public function getMasks()
	{
		return array(
			array(
				array('10000', '01001'),
				'010010000'
			),
			array(
				array('1111', '0000'),
				'11110000'
			)
		);
	}
}