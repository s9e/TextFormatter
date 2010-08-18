<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;
use s9e\Toolkit\Acl\Role;

include_once __DIR__ . '/../Acl.php';
include_once __DIR__ . '/../Role.php';

class RoleTest extends \PHPUnit_Framework_TestCase
{
	public function testSimpleRole()
	{
		$admin = new Role('admin');
		$admin->allow('administer');
		$admin->addRule('administer', 'grant', 'supervise');

		$user  = new Acl;

		$this->assertFalse($user->getReader()->isAllowed('administer'));

		$user->import($admin);

		$this->assertTrue($user->getReader()->isAllowed('administer'));
		$this->assertTrue($user->getReader()->isAllowed('supervise'));
	}
}