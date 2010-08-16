<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Builder;
use s9e\Toolkit\Acl\Reader;
use s9e\Toolkit\Acl\Resource;

include_once __DIR__ . '/../Builder.php';
include_once __DIR__ . '/../Reader.php';
include_once __DIR__ . '/../Resource.php';

class ResourceTest extends \PHPUnit_Framework_TestCase
{
	public function testIsAllowed()
	{
		$user          = new User(123);
		$someOtherUser = new User(456);

		$forum3 = new Forum(3);
		$forum5 = new Forum(5);

		// The user can read in forums 3 and 5, write in forum 5
		$user->acl()->allow('read', $forum3);
		$user->acl()->allow('read', $forum5);
		$user->acl()->allow('post', $forum5);

		// The user can edit their own stuff
		$user->acl()->allow('edit', array('author' => 123));

		$this->assertTrue($user->can('read', $forum3));
		$this->assertFalse($user->can('post', $forum3));
		$this->assertTrue($user->can('read', $forum5));
		$this->assertTrue($user->can('post', $forum5));

		// Resources can be used in values as well
		$this->assertTrue($user->can('read', array('forum' => $forum3)));
		$this->assertFalse($user->can('post', array('forum' => $forum3)));

		// The user has posted a topic in forum 3
		$topic44 = new Topic(44, $forum3, $user);
		$this->assertTrue($user->can('read', $topic44));
		$this->assertTrue($user->can('edit', $topic44));

		// Someone else has posted a topic in forum 3
		$topic55 = new Topic(55, $forum3, $someOtherUser);
		$this->assertTrue($user->can('read', $topic55));
		$this->assertFalse($user->can('edit', $topic55));

		// Let's give that user the right to edit that specific topic even if it's not theirs
		$user->acl()->allow('edit', $topic55);
		$this->assertTrue($user->can('edit', $topic55));
	}

	public function testGetPredicate()
	{
		$user  = new User(123);
		$forum = new Forum(5);
		$acl   = $user->acl();

		$acl->allow('foo', array('forum' => $forum, 'bar' => 'baz'));
		$acl->allow('foo', array('forum' => $forum, 'bar' => 'quux'));

		$this->assertEquals(
			array('type' => 'some', 'which' => array('baz', 'quux')),
			$acl->getReader()->getPredicate('foo', 'bar', $forum)
		);

		$this->assertEquals(
			array('type' => 'some', 'which' => array('baz', 'quux')),
			$acl->getReader()->getPredicate('foo', 'bar', array('forum' => $forum))
		);
	}
}

class Forum implements Resource
{
	public $id;

	public function __construct($id)
	{
		$this->id = $id;
	}

	public function getAclId()
	{
		return $this->id;
	}

	public function getAclResourceName()
	{
		return 'forum';
	}

	public function getAclAttributes()
	{
		return array('forum' => $this->id);
	}
}

class Topic implements Resource
{
	public $id;
	public $forum;
	public $author;

	public function __construct($id, Forum $forum, User $author)
	{
		$this->id     = $id;
		$this->forum  = $forum;
		$this->author = $author;
	}

	public function getAclId()
	{
		return $this->id;
	}

	public function getAclResourceName()
	{
		return 'topic';
	}

	public function getAclAttributes()
	{
		return array(
			'topic'  => $this->id,
			'forum'  => $this->forum->id,
			'author' => $this->author->id
		);
	}
}

class User
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
			$this->acl = new Builder;
		}

		return $this->acl;
	}

	public function can($perm, $scope = null)
	{
		// in a real application, the reader should be cached for performance
		return $this->acl->getReader()->isAllowed($perm, $scope);
	}
}