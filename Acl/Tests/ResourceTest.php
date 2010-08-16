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
	public function test()
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
		$topic = new Topic(44, $forum3, $user);
		$this->assertTrue($user->can('read', $topic));
		$this->assertTrue($user->can('edit', $topic));

		// Someone else has posted a topic in forum 3
		$topic = new Topic(44, $forum3, $someOtherUser);
		$this->assertTrue($user->can('read', $topic));
		$this->assertFalse($user->can('edit', $topic));

		// Let's give that user the right to edit that topic even if it's not theirs
		$user->acl()->allow('edit', $topic);
		$this->assertTrue($user->can('edit', $topic));
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