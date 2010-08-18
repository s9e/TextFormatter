<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Builder;
use s9e\Toolkit\Acl\Reader;
use s9e\Toolkit\Acl\Resource;

include_once __DIR__ . '/../Builder.php';
include_once __DIR__ . '/../Resource.php';

class AdvancedForumAclTest extends \PHPUnit_Framework_TestCase
{
	public function testForumStuff()
	{
		//======================================================================
		// Bootstrapping
		//======================================================================

		// Create a couple of users
		$user          = new User(123);
		$someOtherUser = new User(456);

		// Create a couple of forums
		$forum3 = new Forum(3);
		$forum5 = new Forum(5);

		// The user can read stuff posted in forum 3
		$user->acl()->allow('read', $forum3);
		$user->acl()->allow('read', $forum5);
		$user->acl()->allow('post', $forum5);

		// The user can edit their own posts as long as neither the post or topic is locked
		$user->acl()->allow('edit', array(
			'post.author.id' => $user->id,
			'post.locked'    => 0,
			'topic.locked'   => 0
		));

		// The user is a moderator in forum 5
		$user->acl()->allow('moderate', $forum5);

		// Moderators can do anything they want in their forums
		$user->acl()->addRule('moderate', 'grant', 'read')
		            ->addRule('moderate', 'grant', 'post')
		            ->addRule('moderate', 'grant', 'edit')
		            ->addRule('moderate', 'grant', 'delete')
		            ->addRule('moderate', 'grant', 'lock');

		//======================================================================
		// Real stuff
		//======================================================================

		// The user has posted a topic in forum 3
		$topic44         = new Topic(44, $forum3, $user, false);
		$postFromTopic44 = new Post(444, $topic44, $user, false);

		// Can the user read this topic? Yes!
		$this->assertTrue($user->can('read', $topic44));

		// Can the user edit their post? Yes!
		$this->assertTrue($user->can('edit', $postFromTopic44));

		// Let's lock this topic
		$topic44->locked = 1;

		// Can the user edit their post? No, because the topic is locked
		$this->assertFalse($user->can('edit', $postFromTopic44));

		// Someone else has posted a topic in forum 3
		$topic55         = new Topic(55, $forum3, $someOtherUser, false);
		$postFromTopic55 = new Post(555, $topic55, $someOtherUser, false);

		// The user can read it, it's in forum 3
		$this->assertTrue($user->can('read', $topic55));

		// Can't edit that post though, it's not theirs
		$this->assertFalse($user->can('edit', $topic55));

		// Let's move that topic to forum 5
		$topic55->forum = $forum5;

		// Now the user can edit that post because they're a moderator
		$this->assertTrue($user->can('edit', $postFromTopic55));
	}
}

class Forum implements Resource
{
	public $id;

	public function __construct($id)
	{
		$this->id = $id;
	}

	public function getAclBuilderScope()
	{
		return array('forum.id' => $this->id);
	}

	public function getAclReaderScope()
	{
		return array('forum.id' => $this->id);
	}
}

class Topic implements Resource
{
	public $id;
	public $forum;
	public $author;
	public $locked;

	public function __construct($id, Forum $forum, User $author, $locked)
	{
		$this->id     = $id;
		$this->forum  = $forum;
		$this->author = $author;
		$this->locked = (int) $locked;
	}

	public function getAclBuilderScope()
	{
		return array('topic.id' => $this->id);
	}

	public function getAclReaderScope()
	{
		$topicScope = array(
			'topic.id'        => $this->id,
			'topic.author.id' => $this->author->id,
			'topic.locked'    => $this->locked
		);

		return $topicScope + $this->forum->getAclReaderScope();
	}
}

class Post implements Resource
{
	public $id;
	public $topic;
	public $author;
	public $locked;

	public function __construct($id, Topic $topic, User $author, $locked)
	{
		$this->id     = $id;
		$this->topic  = $topic;
		$this->author = $author;
		$this->locked = (int) $locked;
	}

	public function getAclBuilderScope()
	{
		return array('post.id' => $this->id);
	}

	public function getAclReaderScope()
	{
		$postScope = array(
			'post.id'        => $this->id,
			'post.author.id' => $this->author->id,
			'post.locked'    => $this->locked
		);

		return $postScope + $this->topic->getAclReaderScope();
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