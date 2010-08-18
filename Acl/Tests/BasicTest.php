<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Builder;
use s9e\Toolkit\Acl\Reader;

include_once __DIR__ . '/../Builder.php';

class BasicTest extends \PHPUnit_Framework_TestCase
{
	public function testOneGlobalPerm()
	{
		$builder = new Builder;
		$builder->allow('foo');

		$this->assertTrue($builder->getReader()->isAllowed('foo'));
	}

	public function testMultiGlobalPerms()
	{
		$builder = new Builder;
		$builder->allow('foo');
		$builder->deny('bar');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
	}

	public function testUnknownPermsReturnFalse()
	{
		$builder = new Builder;
		$builder->allow('foo');

		$this->assertFalse($builder->getReader()->isAllowed('bar'));
	}

	public function testPermsCanBeUsedAsMagicMethod()
	{
		$builder = new Builder;
		$builder->allow('foo');

		$this->assertTrue($builder->getReader()->foo());
	}

	public function testDenyOverridesAllow()
	{
		$builder = new Builder;
		$builder->deny('foo');
		$builder->allow('foo');

		$this->assertFalse($builder->getReader()->isAllowed('foo'));
	}

	public function testReaderCanBeSerializedWithoutLosingStuff()
	{
		$builder = new Builder;
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
		$builder = new Builder;
		$builder->allow('foo');

		$builder->getReader()->isAllowed('foo', 123);
	}

	/**
	* @expectedException \InvalidArgumentException
	*/
	public function testBuilderRejectsNonArrayScope()
	{
		$builder = new Builder;
		$builder->allow('foo', 123);
	}

	/**
	* @expectedException \InvalidArgumentException
	*/
	public function testBuilderRejectsNonStringNonIntegerScopeValues()
	{
		$builder = new Builder;
		$builder->allow('foo', array('scope' => true));
	}

	public function testBuilderAllowIsChainable()
	{
		$builder = new Builder;
		$this->assertSame($builder, $builder->allow('foo'));
	}

	public function testBuilderDenyIsChainable()
	{
		$builder = new Builder;
		$this->assertSame($builder, $builder->deny('foo'));
	}

	public function testBuilderAddRuleIChainable()
	{
		$builder = new Builder;
		$this->assertSame($builder, $builder->addRule('foo', 'grant', 'bar'));
	}
}