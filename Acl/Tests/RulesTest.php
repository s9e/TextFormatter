<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;

include_once __DIR__ . '/../Acl.php';

class RulesTest extends \PHPUnit_Framework_TestCase
{
	public function testGlobalGrant()
	{
		$acl = new Acl;
		$acl->allow('foo');
		$acl->addRule('foo', 'grant', 'bar');

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('bar'));
	}

	public function testLocalGrant()
	{
		$acl = new Acl;
		$acl->allow('foo', array('scope' => 123));
		$acl->addRule('foo', 'grant', 'bar');

		$reader = $acl->getReader();

		$this->assertFalse($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
		$this->assertTrue($reader->isAllowed('foo', array('scope' => 123)));
		$this->assertTrue($reader->isAllowed('bar', array('scope' => 123)));
	}

	public function testGrantDoesNotOverrideGlobalDeny()
	{
		$acl = new Acl;
		$acl->allow('foo');
		$acl->deny('bar');
		$acl->addRule('foo', 'grant', 'bar');

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
	}

	public function testGrantDoesNotOverrideLocalDeny()
	{
		$acl = new Acl;
		$acl->allow('foo');
		$acl->deny('bar', array('scope' => 123));
		$acl->addRule('foo', 'grant', 'bar');

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('bar'));
		$this->assertFalse($reader->isAllowed('bar', array('scope' => 123)));
	}

	public function testGrantedPermsCanGrantOtherPerms()
	{
		$acl = new Acl;
		$acl->allow('foo');

		$acl->addRule('bar', 'grant', 'baz');
		$acl->addRule('foo', 'grant', 'bar');

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('bar'));
		$this->assertTrue($reader->isAllowed('baz'));
	}

	public function testFulfilledRequireDoesNotInterfere()
	{
		$acl = new Acl;
		$acl->allow('foo');
		$acl->allow('bar');

		$acl->addRule('bar', 'require', 'foo');

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('bar'));
	}

	public function testUnfulfilledRequireUnsetsPerm()
	{
		$acl = new Acl;
		$acl->allow('foo');
		$acl->allow('bar');

		$acl->addRule('bar', 'require', 'baz');

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
	}

	public function testRevokedGrantsCannotGrantOtherPerms()
	{
		$acl = new Acl;
		$acl->allow('foo');

		$acl->addRule('foo', 'grant', 'bar');
		$acl->addRule('bar', 'grant', 'baz');
		$acl->addRule('bar', 'require', 'quux');

		$reader = $acl->getReader();

		$this->assertFalse($reader->isAllowed('bar'));
		$this->assertFalse($reader->isAllowed('baz'));
	}

	public function testRevokedGrantsCannotGrantOtherPermsNoMatterHowIndirectly()
	{
		$acl = new Acl;
		$acl->allow('foo');

		$acl->addRule('foo', 'grant', 'bar');
		$acl->addRule('bar', 'grant', 'baz');
		$acl->addRule('baz', 'grant', 'quux');
		$acl->addRule('bar', 'require', 'waldo');

		$reader = $acl->getReader();

		$this->assertFalse($reader->isAllowed('bar'));
		$this->assertFalse($reader->isAllowed('baz'));
		$this->assertFalse($reader->isAllowed('quux'));
	}

	public function testRevokedGrantsDoNotInterfereWithOtherGrants()
	{
		$acl = new Acl;
		$acl->allow('foo');
		$acl->allow('waldo');

		$acl->addRule('foo', 'grant', 'bar');
		$acl->addRule('bar', 'grant', 'baz');
		$acl->addRule('waldo', 'grant', 'baz');
		$acl->addRule('bar', 'require', 'quux');

		$reader = $acl->getReader();

		$this->assertFalse($reader->isAllowed('bar'));
		$this->assertTrue($reader->isAllowed('baz'));
	}

	public function testRequireOnAPermWithDifferentDimensions()
	{
		$acl = new Acl;

		$acl->allow('foo');
		$acl->allow('bar', array('x' => 1));
		$acl->allow('bar', array('y' => 1));

		$acl->addRule('bar', 'require', 'foo');

		$reader = $acl->getReader();

		$this->assertFalse($reader->isAllowed('bar'));
		$this->assertTrue($reader->isAllowed('bar', array('x' => 1)));
		$this->assertTrue($reader->isAllowed('bar', array('y' => 1)));
		$this->assertTrue($reader->isAllowed('bar', array('x' => 1, 'y' => 1)));
	}
}