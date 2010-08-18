<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;

include_once __DIR__ . '/../Acl.php';

class BasicTest extends \PHPUnit_Framework_TestCase
{
	public function testOneGlobalPerm()
	{
		$acl = new Acl;
		$acl->allow('foo');

		$this->assertTrue($acl->getReader()->isAllowed('foo'));
	}

	public function testMultiGlobalPerms()
	{
		$acl = new Acl;
		$acl->allow('foo');
		$acl->deny('bar');

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
	}

	public function testUnknownPermsReturnFalse()
	{
		$acl = new Acl;
		$acl->allow('foo');

		$this->assertFalse($acl->getReader()->isAllowed('bar'));
	}

	public function testDenyOverridesAllow()
	{
		$acl = new Acl;
		$acl->deny('foo');
		$acl->allow('foo');

		$this->assertFalse($acl->getReader()->isAllowed('foo'));
	}

	public function testReaderCanBeSerializedWithoutLosingStuff()
	{
		$acl = new Acl;
		$acl->allow('foo');

		$reader  = $acl->getReader();
		$reader2 = unserialize(serialize($reader));

		$this->assertEquals($reader2, $reader);
	}

	/**
	* @expectedException \InvalidArgumentException
	*/
	public function testReaderRejectsNonArrayScope()
	{
		$acl = new Acl;
		$acl->allow('foo');

		$acl->getReader()->isAllowed('foo', 123);
	}

	/**
	* @expectedException \InvalidArgumentException
	*/
	public function testAclRejectsNonArrayScope()
	{
		$acl = new Acl;
		$acl->allow('foo', 123);
	}

	/**
	* @expectedException \InvalidArgumentException
	*/
	public function testAclRejectsNonScalarScopeValues()
	{
		$acl = new Acl;
		$acl->allow('foo', array('scope' => new \stdClass));
	}

	public function testAclAllowIsChainable()
	{
		$acl = new Acl;
		$this->assertSame($acl, $acl->allow('foo'));
	}

	public function testAclDenyIsChainable()
	{
		$acl = new Acl;
		$this->assertSame($acl, $acl->deny('foo'));
	}

	public function testAclAddRuleIsChainable()
	{
		$acl = new Acl;
		$this->assertSame($acl, $acl->addRule('foo', 'grant', 'bar'));
	}

	public function testAclImportIsChainable()
	{
		$acl = new Acl;
		$this->assertSame($acl, $acl->import(new Acl));
	}

	public function testAclAcceptsBooleanScopeValues()
	{
		$acl = new Acl;
		$acl->allow('foo', array('scope' => true));
		$acl->allow('bar', array('scope' => false));
	}

	/**
	* @depends testAclAcceptsBooleanScopeValues
	*/
	public function testReaderWorksWithBooleanScopeValues()
	{
		$acl = new Acl;
		$acl->allow('foo', array('scope' => true));
		$acl->allow('bar', array('scope' => false));

		$reader = $acl->getReader();
		$this->assertTrue($reader->isAllowed('foo', array('scope' => true)));
		$this->assertFalse($reader->isAllowed('foo', array('scope' => false)));
		$this->assertFalse($reader->isAllowed('bar', array('scope' => true)));
		$this->assertTrue($reader->isAllowed('bar', array('scope' => false)));
	}

	public function testAclAcceptsFloatScopeValues()
	{
		$acl = new Acl;
		$acl->allow('foo', array('scope' => 1 / 3));
		$acl->allow('bar', array('scope' => 0.5));
	}

	/**
	* @depends testAclAcceptsFloatScopeValues
	*/
	public function testReaderWorksWithFloatScopeValues()
	{
		$acl = new Acl;
		$acl->allow('foo', array('scope' => 1 / 3));
		$acl->allow('bar', array('scope' => 0.5));

		$reader = $acl->getReader();
		$this->assertTrue($reader->isAllowed('foo', array('scope' => 1 / 3)));
		$this->assertTrue($reader->isAllowed('bar', array('scope' => 0.5)));
		$this->assertFalse($reader->isAllowed('bar', array('scope' => 0)));
		$this->assertFalse($reader->isAllowed('bar', array('scope' => 1)));
	}

	public function testAclCanBeQueriedDirectly()
	{
		$acl = new Acl;
		$acl->allow('foo');

		$this->assertTrue($acl->isAllowed('foo'));
	}

	/**
	* @depends testAclCanBeQueriedDirectly
	*/
	public function testAclCanBeQueriedDirectlyWithoutReturningStaleResults()
	{
		$acl = new Acl;
		$this->assertFalse($acl->isAllowed('foo'));
		$acl->allow('foo');
		$this->assertTrue($acl->isAllowed('foo'));
	}

	/**
	* @depends testAclCanBeQueriedDirectly
	*/
	public function testAclCanBeQueriedUsingAny()
	{
		$acl = new Acl;
		$acl->allow('foo', array('scope' => 1));
		$this->assertTrue($acl->isAllowed('foo', array('scope' => $acl->any())));
	}
}