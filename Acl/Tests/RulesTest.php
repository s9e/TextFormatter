<?php

namespace s9e\Toolkit\Acl;

include_once __DIR__ . '/../Builder.php';
include_once __DIR__ . '/../Reader.php';

class RulesTest extends \PHPUnit_Framework_TestCase
{
	public function testGlobalGrant()
	{
		$builder = new Builder;
		$builder->allow('foo');
		$builder->addRule('foo', 'grant', 'bar');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('bar'));
	}

	public function testLocalGrant()
	{
		$builder = new Builder;
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
		$builder = new Builder;
		$builder->allow('foo');
		$builder->deny('bar');
		$builder->addRule('foo', 'grant', 'bar');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
	}

	public function testGrantDoesNotOverrideLocalDeny()
	{
		$builder = new Builder;
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
		$builder = new Builder;
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
		$builder = new Builder;
		$builder->allow('foo');
		$builder->allow('bar');

		$builder->addRule('bar', 'require', 'foo');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertTrue($reader->isAllowed('bar'));
	}

	public function testUnfulfilledRequireUnsetsPerm()
	{
		$builder = new Builder;
		$builder->allow('foo');
		$builder->allow('bar');

		$builder->addRule('bar', 'require', 'baz');

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('bar'));
	}

	public function testRevokedGrantsCannotGrantOtherPerms()
	{
		$builder = new Builder;
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
		$builder = new Builder;
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
		$builder = new Builder;
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

	public function testRequireOnAPermWithDifferentDimensions()
	{
		$builder = new Builder;

		$builder->allow('foo');
		$builder->allow('bar', array('x' => 1));
		$builder->allow('bar', array('y' => 1));

		$builder->addRule('bar', 'require', 'foo');

		$reader = $builder->getReader();

		$this->assertFalse($reader->isAllowed('bar'));
		$this->assertTrue($reader->isAllowed('bar', array('x' => 1)));
		$this->assertTrue($reader->isAllowed('bar', array('y' => 1)));
		$this->assertTrue($reader->isAllowed('bar', array('x' => 1, 'y' => 1)));
	}
}