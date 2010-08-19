<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;
use s9e\Toolkit\Acl\Predicate;

include_once __DIR__ . '/../Acl.php';

class PredicateTest extends \PHPUnit_Framework_TestCase
{
	public function testPredicateSome()
	{
		$acl = new Acl;
		$acl->allow('read', array('forum_id' => 3));
		$acl->allow('read', array('forum_id' => 4));
		$acl->allow('read', array('forum_id' => 5));

		$actual   = $acl->getPredicate('read', 'forum_id');
		$expected = array(
			'type'  => 'some',
			'which' => array(3, 4, 5)
		);

		$this->assertEquals($expected, $actual);
	}

	/**
	* @depends testPredicateSome
	*/
	public function testAclCanBeQueriedForPredicateDirectly()
	{
		$acl = new Acl;
		$acl->allow('read', array('forum_id' => 3));
		$acl->allow('read', array('forum_id' => 4));
		$acl->allow('read', array('forum_id' => 5));

		$actual   = $acl->getPredicate('read', 'forum_id');
		$expected = array(
			'type'  => 'some',
			'which' => array(3, 4, 5)
		);

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateAllBut()
	{
		$acl = new Acl;
		$acl->allow('read');
		$acl->deny('read', array('forum_id' => 4));
		$acl->deny('read', array('forum_id' => 5));

		$actual   = $acl->getPredicate('read', 'forum_id');
		$expected = array(
			'type'  => 'all_but',
			'which' => array(4, 5)
		);

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateAll()
	{
		$acl = new Acl;
		$acl->allow('read');

		$actual   = $acl->getPredicate('read', 'forum_id');
		$expected = array('type' => 'all');

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateNone()
	{
		$acl = new Acl;
		$acl->deny('read');

		$actual   = $acl->getPredicate('read', 'forum_id');
		$expected = array('type' => 'none');

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateNoneOnUnknownPerm()
	{
		$acl = new Acl;

		$actual   = $acl->getPredicate('read', 'forum_id');
		$expected = array('type' => 'none');

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateWithScope()
	{
		$acl = new Acl;
		$acl->allow('perm', array('x' => 1, 'y' => 4));
		$acl->allow('perm', array('x' => 2, 'y' => 4));
		$acl->allow('perm', array('x' => 2, 'y' => 5));
		$acl->allow('perm', array('x' => 3, 'y' => 5));

		$reader  = $acl;

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

	public function testPredicateWithLocalWildcardScope()
	{
		$acl = new Acl;
		$acl->allow('perm', array('x' => 1, 'y' => 4));
		$acl->allow('perm', array('x' => 2, 'y' => 4));
		$acl->allow('perm', array('x' => 2, 'y' => 5));
		$acl->allow('perm', array('x' => 3, 'y' => 5));
		$acl->deny('perm', array('x' => 9));
		$acl->deny('perm', array('y' => 9));

		$reader  = $acl;

		$actual   = $reader->getPredicate('perm', 'x', array('y' => $reader->wildcard()));
		$expected = array(
			'type'  => 'some',
			'which' => array(1, 2, 3)
		);

		$this->assertEquals($expected, $actual);

		$actual   = $reader->getPredicate('perm', 'y', array('x' => $reader->wildcard()));
		$expected = array(
			'type'  => 'some',
			'which' => array(4, 5)
		);

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateWithGlobalWildcardScope()
	{
		$acl = new Acl;
		$acl->allow('perm', array('x' => 1, 'y' => 4));
		$acl->allow('perm', array('x' => 2, 'y' => 4));
		$acl->allow('perm', array('x' => 2, 'y' => 5));
		$acl->allow('perm', array('x' => 3, 'y' => 5));
		$acl->deny('perm', array('x' => 9));
		$acl->deny('perm', array('y' => 9));

		$reader  = $acl;

		$actual   = $reader->getPredicate('perm', 'x', $reader->wildcard());
		$expected = array(
			'type'  => 'some',
			'which' => array(1, 2, 3)
		);

		$this->assertEquals($expected, $actual);

		$actual   = $reader->getPredicate('perm', 'y', $reader->wildcard());
		$expected = array(
			'type'  => 'some',
			'which' => array(4, 5)
		);

		$this->assertEquals($expected, $actual);
	}

	public function testGlobalSettingWithLocalScope()
	{
		$acl = new Acl;
		$acl->allow('perm');
		$acl->deny('perm', array('x' => 1, 'y' => 1, 'z' => 1));
		
		$actual   = $acl->getPredicate('perm', 'x', array('y' => 1));
		$expected = array(
			'type'  => 'all'
		);

		$this->assertEquals($expected, $actual);
	}

	public function testExpectedPredicateAllWithLocalScope()
	{
		$acl = new Acl;
		$acl->allow('perm', array('x' => 1, 'y' => 1, 'z' => 0));
		$acl->deny('perm', array('x' => 1, 'y' => 1, 'z' => 1));
		
		$actual   = $acl->getPredicate('perm', 'x', array('y' => 1));
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
		$acl = new Acl;
		$acl->allow('read', array('forum_id' => 4));

		$acl->getPredicate('read', 'forum_id', array('forum_id' => 4));
	}

	/**
	* @expectedException InvalidArgumentException
	*/
	public function testPredicateWithNonArrayNotWildcardScopeThrowsAnException()
	{
		$acl = new Acl;
		$acl->allow('read', array('forum_id' => 4));

		$acl->getPredicate('read', 'forum_id', true);
	}
}