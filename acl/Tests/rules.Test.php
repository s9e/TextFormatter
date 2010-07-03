<?php

namespace s9e\toolkit\acl;

include_once __DIR__ . '/../builder.php';
include_once __DIR__ . '/../reader.php';

class testRules extends \PHPUnit_Framework_TestCase
{
	public function testGlobalGrant()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->addRule('foo', 'grant', 'bar');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('bar'));
	}

	public function testLocalGrant()
	{
		$builder = new builder;
		$builder->allow('foo', array('scope' => 123));
		$builder->addRule('foo', 'grant', 'bar');

		$reader = $builder->getReader();

		$this->assertFalse($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
		$this->assertTrue($reader->isAllowed('foo', array('scope' => 123)));
		$this->assertTrue($reader->isAllowed('bar', array('scope' => 123)));
	}

	public function testGrantDoesNotOverrideGlobalDeny()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->deny('bar');
		$builder->addRule('foo', 'grant', 'bar');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
	}

	public function testGrantDoesNotOverrideLocalDeny()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->deny('bar', array('scope' => 123));
		$builder->addRule('foo', 'grant', 'bar');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('bar'));
		$this->assertFalse($reader->isAllowed('bar', array('scope' => 123)));
	}

	public function testGrantedPermsCanGrantOtherPerms()
	{
		$builder = new builder;
		$builder->allow('foo');

		$builder->addRule('bar', 'grant', 'baz');
		$builder->addRule('foo', 'grant', 'bar');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('bar'));
		$this->assertTrue($reader->isAllowed('baz'));
	}

	public function testFulfilledRequireDoesNotInterfere()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->allow('bar');

		$builder->addRule('bar', 'require', 'foo');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('bar'));
	}

	public function testUnfulfilledRequireUnsetsPerm()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->allow('bar');

		$builder->addRule('bar', 'require', 'baz');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
	}

	public function testRevokedGrantsCannotGrantOtherPerms()
	{
		$builder = new builder;
		$builder->allow('foo');

		$builder->addRule('foo', 'grant', 'bar');
		$builder->addRule('bar', 'grant', 'baz');
		$builder->addRule('bar', 'require', 'quux');

		$reader = $builder->getReader();

		$this->assertFalse($reader->isAllowed('bar'));
		$this->assertFalse($reader->isAllowed('baz'));
	}

	public function testRevokedGrantsCannotGrantOtherPermsNoMatterHowIndirectly()
	{
		$builder = new builder;
		$builder->allow('foo');

		$builder->addRule('foo', 'grant', 'bar');
		$builder->addRule('bar', 'grant', 'baz');
		$builder->addRule('baz', 'grant', 'quux');
		$builder->addRule('bar', 'require', 'waldo');

		$reader = $builder->getReader();

		$this->assertFalse($reader->isAllowed('bar'));
		$this->assertFalse($reader->isAllowed('baz'));
		$this->assertFalse($reader->isAllowed('quux'));
	}

	public function testRevokedGrantsDoNotInterfereWithOtherGrants()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->allow('waldo');

		$builder->addRule('foo', 'grant', 'bar');
		$builder->addRule('bar', 'grant', 'baz');
		$builder->addRule('waldo', 'grant', 'baz');
		$builder->addRule('bar', 'require', 'quux');

		$reader = $builder->getReader();

		$this->assertFalse($reader->isAllowed('bar'));
		$this->assertTrue($reader->isAllowed('baz'));
	}
}