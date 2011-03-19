<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;
use s9e\Toolkit\Acl\Resource;

include_once __DIR__ . '/../../src/Acl/Acl.php';
include_once __DIR__ . '/../../src/Acl/Resource.php';

class ResourceTest extends \PHPUnit_Framework_TestCase
{
	public function testGetPredicate()
	{
		$user  = new MyUser(123);
		$forum = new MyForum(5);
		$acl   = $user->acl();

		$acl->allow('foo', array('forum' => $forum->id, 'bar' => 'baz'));
		$acl->allow('foo', array('forum' => $forum->id, 'bar' => 'quux'));

		$this->assertEquals(
			array('type' => 'some', 'which' => array('baz', 'quux')),
			$acl->getPredicate('foo', 'bar', $forum)
		);

		$this->assertEquals(
			array('type' => 'some', 'which' => array('baz', 'quux')),
			$acl->getPredicate('foo', 'bar', array('forum' => $forum->id))
		);
	}
}

class MyForum implements Resource
{
	public $id;

	public function __construct($id)
	{
		$this->id = $id;
	}

	public function getAclBuilderScope()
	{
		return array('forum' => $this->id);
	}

	public function getAclReaderScope()
	{
		return array('forum' => $this->id);
	}
}

class MyUser
{
	public $id;
	protected $acl;

	public function __construct($id)
	{
		$this->id = $id;
	}

	public function acl()
	{
		if (!isset($this->acl))
		{
			$this->acl = new Acl;
		}

		return $this->acl;
	}

	public function can($perm, $scope = array())
	{
		// in a real application, the reader should be cached for performance
		return $this->acl()->isAllowed($perm, $scope);
	}
}