<?php

namespace s9e\Toolkit\Acl;

include_once __DIR__ . '/../Builder.php';
include_once __DIR__ . '/../Reader.php';

class InternalsTest extends \PHPUnit_Framework_TestCase
{
	public function testIdenticalPermsAreOptimizedAway()
	{
		$builder = new Builder;
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
		$builder = new Builder;
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
		$builder = new Builder;
		$builder->allow('foo');
		$builder->deny('bar');

		$config = $builder->getReaderConfig();

		$this->assertFalse(isset($config['bar']));
	}

	public function testScopesIdenticalToGlobalAreOptimizedAway()
	{
		$builder = new Builder;

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

	public function testPermIsNotOptimizedAwayToAnotherSpaceIfItIsAloneInNewSpace()
	{
		$builder = new Builder;

		/**
		* foo and bar live in the (x,y) space.
		*
		* Dimension y has no bearing on foo, so foo could be moved to space (x)
		* Space (x) does not exist though, and creating a space for one single perm is more
		* expensive in terms of metadata than leaving it in its shared space, so foo stays in (x,y)
		*/
		$builder->allow('foo', array('x' => 1));
		$builder->allow('foo', array('x' => 1, 'y' => 1));

		$builder->allow('bar', array('x' => 1, 'y' => 1));

		$config = $builder->getReaderConfig();

		$this->assertArrayHasKey('x', $config['foo']['scopes']);
		$this->assertArrayHasKey('y', $config['foo']['scopes']);
		$this->assertArrayHasKey('x', $config['bar']['scopes']);
		$this->assertArrayHasKey('y', $config['bar']['scopes']);
	}

	public function testPermIsOptimizedAwayToAnotherSpaceIfItIsNotAloneInNewSpace()
	{
		$builder = new Builder;

		/**
		* foo and bar live in the (x,y) space. baz lives in (x)
		*
		* Dimension y has no bearing on foo, so it is moved to (x) where it will peacefully coexist
		* with baz
		*/
		$builder->allow('foo', array('x' => 1));
		$builder->allow('foo', array('x' => 1, 'y' => 1));

		$builder->allow('bar', array('x' => 1, 'y' => 1));

		$builder->allow('baz', array('x' => 1));

		$config = $builder->getReaderConfig();

		$this->assertArrayHasKey('x', $config['foo']['scopes']);
		$this->assertArrayNotHasKey('y', $config['foo']['scopes']);
		$this->assertArrayHasKey('x', $config['bar']['scopes']);
		$this->assertArrayHasKey('y', $config['bar']['scopes']);
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
			),
			array(
				array('11110', '11000', '11100'),
				'1111000'
			)
		);
	}
}