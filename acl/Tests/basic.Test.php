<?php

namespace s9e\toolkit\acl;

include_once __DIR__ . '/../builder.php';
include_once __DIR__ . '/../reader.php';

class testBasic extends \PHPUnit_Framework_TestCase
{
	public function testOneGlobalPerm()
	{
		$builder = new builder;
		$builder->allow('foo');

		$this->assertTrue($builder->getReader()->isAllowed('foo'));
	}

	public function testMultiGlobalPerms()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->deny('bar');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
	}

	public function testUnknownPermsReturnFalse()
	{
		$builder = new builder;
		$builder->allow('foo');

		$this->assertFalse($builder->getReader()->isAllowed('bar'));
	}

	public function testPermsCanBeUsedAsMagicMethod()
	{
		$builder = new builder;
		$builder->allow('foo');

		$this->assertTrue($builder->getReader()->foo());
	}

	public function testDenyOverridesAllow()
	{
		$builder = new builder;
		$builder->deny('foo');
		$builder->allow('foo');

		$this->assertFalse($builder->getReader()->isAllowed('foo'));
	}

	public function testReaderCanBeSerializedWithoutLosingStuff()
	{
		$builder = new builder;
		$builder->allow('foo');

		$reader  = $builder->getReader();
		$reader2 = unserialize(serialize($reader));

		unset($reader->any);
		unset($reader2->any);

		$this->assertEquals($reader2, $reader);
	}

	/**
	* @expectedException \InvalidArgumentException
	*/
	public function testReaderRejectsNonArrayScope()
	{
		$builder = new builder;
		$builder->allow('foo');

		$builder->getReader()->isAllowed('foo', 123);
	}

	/**
	* @expectedException \PHPUnit_Framework_Error
	*/
	public function testBuilderRejectsNonArrayScope()
	{
		$builder = new builder;
		$builder->allow('foo', 123);
	}

	/**
	* @expectedException \InvalidArgumentException
	*/
	public function testBuilderRejectsNonStringNonIntegerScopeValues()
	{
		$builder = new builder;
		$builder->allow('foo', array('scope' => true));
	}

	public function testPredicateSome()
	{
		$builder = new builder;
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
		$builder = new builder;
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
		$builder = new builder;
		$builder->allow('read');

		$actual   = $builder->getReader()->getPredicate('read', 'forum_id');
		$expected = array('type' => 'all');

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateNone()
	{
		$builder = new builder;
		$builder->deny('read');

		$actual   = $builder->getReader()->getPredicate('read', 'forum_id');
		$expected = array('type' => 'none');

		$this->assertEquals($expected, $actual);
	}

	public function testPredicateNoneOnUnknownPerm()
	{
		$builder = new builder;

		$actual   = $builder->getReader()->getPredicate('read', 'forum_id');
		$expected = array('type' => 'none');

		$this->assertEquals($expected, $actual);
	}
}