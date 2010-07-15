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
			$config['foo']['perms']['foo'],
			$config['bar']['perms']['foo']
		);
	}

	public function testIdenticalScopesAreOptimizedAway()
	{
		$builder = new builder;
		$builder->allow('foo', array('scope' => 123));
		$builder->allow('foo', array('scope' => 456));

		$config = $builder->getReaderConfig();

		$this->assertSame(
			$config['foo']['scopes']['scope'][123],
			$config['foo']['scopes']['scope'][456]
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

	public function testScopesIdenticalToGlobalAreOptimizedAway()
	{
		$builder = new builder;

		$builder->allow('foo', array('x' => 1));
		$builder->allow('foo', array('x' => 1, 'y' => 1));
		$builder->allow('foo', array('x' => 2, 'y' => 2));
		$builder->allow('foo', array('x' => 2));
		$builder->allow('foo', array('x' => 2, 'y' => 1));
		$builder->deny('foo', array('x' => 2, 'y' => 2));

		$config = $builder->getReaderConfig();

		$this->assertArrayHasKey(1, $config['foo']['scopes']['x']);
		$this->assertArrayHasKey(2, $config['foo']['scopes']['x']);
		$this->assertArrayNotHasKey(1, $config['foo']['scopes']['y']);
		$this->assertArrayHasKey(2, $config['foo']['scopes']['y']);
	}

	public function testUnusedDimensionsAreOptimizedAway()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->allow('bar', array('x' => 1));
		$builder->allow('bar', array('y' => 1));
//		$builder->deny('bar', array('y' => 2));

		$builder->addRule('bar', 'require', 'foo');

		$config = $builder->getReaderConfig();

		print_r($config);
	}

	/**
	* @dataProvider getMasks
	*/
	public function testMergeMasks($masks, $expected, $msg = null)
	{
		$method = new \ReflectionMethod(__NAMESPACE__ . '\\builder', 'mergeMasks');
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