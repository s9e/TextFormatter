<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Builder;
use s9e\Toolkit\Acl\Predicate;
use s9e\Toolkit\Acl\Reader;

include_once __DIR__ . '/../Builder.php';
include_once __DIR__ . '/../Reader.php';

class PredicateTest extends \PHPUnit_Framework_TestCase
{
	public function testPredicateSome()
	{
		$builder = new Builder;
		$builder->allow('read', array('forum_id' => 3));
		$builder->allow('read', array('forum_id' => 4));
		$builder->allow('read', array('forum_id' => 5));

		$actual   = $builder->getReader()->getPredicate('read', 'forum_id');
		$expected = array(
			'type'  => 'some',
			'which' => array(3, 4, 5)
		);

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateAllBut()
	{
		$builder = new Builder;
		$builder->allow('read');
		$builder->deny('read', array('forum_id' => 4));
		$builder->deny('read', array('forum_id' => 5));

		$actual   = $builder->getReader()->getPredicate('read', 'forum_id');
		$expected = array(
			'type'  => 'all_but',
			'which' => array(4, 5)
		);

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateAll()
	{
		$builder = new Builder;
		$builder->allow('read');

		$actual   = $builder->getReader()->getPredicate('read', 'forum_id');
		$expected = array('type' => 'all');

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateNone()
	{
		$builder = new Builder;
		$builder->deny('read');

		$actual   = $builder->getReader()->getPredicate('read', 'forum_id');
		$expected = array('type' => 'none');

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateNoneOnUnknownPerm()
	{
		$builder = new Builder;

		$actual   = $builder->getReader()->getPredicate('read', 'forum_id');
		$expected = array('type' => 'none');

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateWithScope()
	{
		$builder = new Builder;
		$builder->allow('perm', array('x' => 1, 'y' => 4));
		$builder->allow('perm', array('x' => 2, 'y' => 4));
		$builder->allow('perm', array('x' => 2, 'y' => 5));
		$builder->allow('perm', array('x' => 3, 'y' => 5));

		$reader  = $builder->getReader();

		$actual   = $reader->getPredicate('perm', 'x', array('y' => 4));
		$expected = array(
			'type'  => 'some',
			'which' => array(1, 2)
		);

		$this->assertEquals($expected, $actual);

		$actual   = $reader->getPredicate('perm', 'x', array('y' => 5));
		$expected = array(
			'type'  => 'some',
			'which' => array(2, 3)
		);

		$this->assertEquals($expected, $actual);

		$actual   = $reader->getPredicate('perm', 'y', array('x' => 1));
		$expected = array(
			'type'  => 'some',
			'which' => array(4)
		);

		$this->assertEquals($expected, $actual);

		$actual   = $reader->getPredicate('perm', 'y', array('x' => 2));
		$expected = array(
			'type'  => 'some',
			'which' => array(4, 5)
		);

		$this->assertEquals($expected, $actual);

		$actual   = $reader->getPredicate('perm', 'y', array('x' => 3));
		$expected = array(
			'type'  => 'some',
			'which' => array(5)
		);

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateWithLocalAnyScope()
	{
		$builder = new Builder;
		$builder->allow('perm', array('x' => 1, 'y' => 4));
		$builder->allow('perm', array('x' => 2, 'y' => 4));
		$builder->allow('perm', array('x' => 2, 'y' => 5));
		$builder->allow('perm', array('x' => 3, 'y' => 5));
		$builder->deny('perm', array('x' => 9));
		$builder->deny('perm', array('y' => 9));

		$reader  = $builder->getReader();

		$actual   = $reader->getPredicate('perm', 'x', array('y' => $reader->any));
		$expected = array(
			'type'  => 'some',
			'which' => array(1, 2, 3)
		);

		$this->assertEquals($expected, $actual);

		$actual   = $reader->getPredicate('perm', 'y', array('x' => $reader->any));
		$expected = array(
			'type'  => 'some',
			'which' => array(4, 5)
		);

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateWithGlobalAnyScope()
	{
		$builder = new Builder;
		$builder->allow('perm', array('x' => 1, 'y' => 4));
		$builder->allow('perm', array('x' => 2, 'y' => 4));
		$builder->allow('perm', array('x' => 2, 'y' => 5));
		$builder->allow('perm', array('x' => 3, 'y' => 5));
		$builder->deny('perm', array('x' => 9));
		$builder->deny('perm', array('y' => 9));

		$reader  = $builder->getReader();

		$actual   = $reader->getPredicate('perm', 'x', $reader->any);
		$expected = array(
			'type'  => 'some',
			'which' => array(1, 2, 3)
		);

		$this->assertEquals($expected, $actual);

		$actual   = $reader->getPredicate('perm', 'y', $reader->any);
		$expected = array(
			'type'  => 'some',
			'which' => array(4, 5)
		);

		$this->assertEquals($expected, $actual);
	}

	public function testGlobalSettingWithLocalScope()
	{
		$builder = new Builder;
		$builder->allow('perm');
		$builder->deny('perm', array('x' => 1, 'y' => 1, 'z' => 1));
		
		$actual   = $builder->getReader()->getPredicate('perm', 'x', array('y' => 1));
		$expected = array(
			'type'  => 'all'
		);

		$this->assertEquals($expected, $actual);
	}

	public function testExpectedPredicateAllWithLocalScope()
	{
		$builder = new Builder;
		$builder->allow('perm', array('x' => 1, 'y' => 1, 'z' => 0));
		$builder->deny('perm', array('x' => 1, 'y' => 1, 'z' => 1));
		
		$actual   = $builder->getReader()->getPredicate('perm', 'x', array('y' => 1));
		$expected = array(
			'type'  => 'none'
		);

		$this->assertEquals($expected, $actual);
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testPredicateWithSameScopeThrowsAnException()
	{
		$builder = new Builder;
		$builder->allow('read', array('forum_id' => 4));

		$builder->getReader()->getPredicate('read', 'forum_id', array('forum_id' => 4));
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testPredicateWithNonArrayNotAnyScopeThrowsAnException()
	{
		$builder = new Builder;
		$builder->allow('read', array('forum_id' => 4));

		$builder->getReader()->getPredicate('read', 'forum_id', true);
	}
}