<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;
use s9e\Toolkit\Acl\Role;

include_once __DIR__ . '/../../src/Acl/Acl.php';
include_once __DIR__ . '/../../src/Acl/Role.php';

class RoleTest extends \PHPUnit_Framework_TestCase
{
	public function testSimpleRole()
	{
		$admin = new Role('admin');
		$admin->allow('administer');
		$admin->addRule('administer', 'grant', 'supervise');

		$user  = new Acl;

		$this->assertFalse($user->isAllowed('administer'));

		$user->addParent($admin);

		$this->assertTrue($user->isAllowed('administer'));
		$this->assertTrue($user->isAllowed('supervise'));
	}
}