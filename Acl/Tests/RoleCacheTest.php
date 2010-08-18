<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Builder;
use s9e\Toolkit\Acl\Reader;
use s9e\Toolkit\Acl\Role;
use s9e\Toolkit\Acl\RoleCache;

include_once __DIR__ . '/../Builder.php';
include_once __DIR__ . '/../Role.php';
include_once __DIR__ . '/../RoleCache.php';

class RoleCacheTest extends \PHPUnit_Framework_TestCase
{
	public function testSimpleRole()
	{
		$user = new Builder;
		$this->assertFalse($user->getReader()->isAllowed('administer'));
		$user->import($this->roleCache->get('admin'));
		$this->assertTrue($user->getReader()->isAllowed('administer'));
	}

	/**
	* @expectedException \InvalidArgumentException
	*/
	public function testAddDoesNotOverwriteRolesByDefault()
	{
		$this->roleCache->add(new Role('admin'));
	}

	/**
	* @depends testSimpleRole
	*/
	public function testCachedRolesCanBeOverwritten()
	{
		$this->roleCache->add(new Role('admin'), true);

		$user = new Builder;
		$this->assertFalse($user->getReader()->isAllowed('administer'));
		$user->import($this->roleCache->get('admin'));
		$this->assertFalse($user->getReader()->isAllowed('administer'));
	}

	/**
	* @expectedException \InvalidArgumentException
	*/
	public function testGetOnInexistentRoleThrowsAnException()
	{
		$this->roleCache->get('inexistent');
	}

	public function testExists()
	{
		$this->assertTrue($this->roleCache->exists('admin'));
	}

	/**
	* @depends testExists
	*/
	public function testClear()
	{
		$this->roleCache->clear();
		$this->assertFalse($this->roleCache->exists('admin'));
	}

	/**
	* @depends testExists
	*/
	public function testRemove()
	{
		$this->roleCache->add(new Role('foo'));

		$this->roleCache->remove('admin');
		$this->assertFalse($this->roleCache->exists('admin'));
		$this->assertTrue($this->roleCache->exists('foo'));
	}

	public function setUp()
	{
		$this->roleCache = new RoleCache;

		$admin = new Role('admin');
		$admin->allow('administer');

		$this->roleCache->add($admin);
	}
}