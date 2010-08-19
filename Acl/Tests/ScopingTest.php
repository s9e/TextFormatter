<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;

include_once __DIR__ . '/../Acl.php';

class ScopingTest extends \PHPUnit_Framework_TestCase
{
	public function testGlobalDenyOverridesLocalAllow()
	{
		$acl = new Acl;
		$acl->deny('foo');
		$acl->allow('foo', array('bar' => 123));

		$this->assertFalse($acl->isAllowed('foo', array('bar' => 123)));
	}

	public function testLocalDenyOverridesGlobalAllow()
	{
		$acl = new Acl;
		$acl->allow('foo');
		$acl->deny('foo', array('bar' => 123));

		$this->assertTrue($acl->isAllowed('foo'));
		$this->assertFalse($acl->isAllowed('foo', array('bar' => 123)));
	}

	public function testUnknownScopeDefaultsToGlobalScope()
	{
		$acl = new Acl;
		$acl->allow('foo');

		$this->assertTrue($acl->isAllowed('foo', array('bar' => 123)));
	}

	public function test2DInheritsFrom1D()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));
		$acl->allow('foo', array('bar' => 456));

		$this->assertFalse($acl->isAllowed('foo', array('bar' => 123)));
		$this->assertTrue($acl->isAllowed('foo', array('bar' => 123, 'baz' => 'xyz')));
		$this->assertTrue($acl->isAllowed('foo', array('bar' => 456)));
		$this->assertTrue($acl->isAllowed('foo', array('bar' => 456, 'baz' => 'xyz')));
	}

	public function testGlobalWildcardOn2DAllow()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$this->assertTrue($acl->isAllowed('foo', $acl->wildcard()));
	}

	public function test2DWildcardOn2DAllow()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$this->assertTrue($acl->isAllowed('foo', array('bar' => $acl->wildcard(), 'baz' => 'xyz')));
		$this->assertTrue($acl->isAllowed('foo', array('bar' => 123, 'baz' => $acl->wildcard())));
	}

	public function test1DWildcardOn2DAllow()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$this->assertFalse($acl->isAllowed('foo', array('bar' => $acl->wildcard())));
		$this->assertFalse($acl->isAllowed('foo', array('baz' => $acl->wildcard())));
	}

	/**
	* Regression test for a bug where the "wildcard" bits are generated in the wrong order because
	* dimensions are not in alphabetical order
	*/
	public function testWildcardOn3DAllow()
	{
		$acl = new Acl;
		$acl->allow('bbcode_use', array(
			'forum_id' => 3
		));

		$acl->allow('bbcode_use', array(
			'in'        => 'sig',
			'bbcode_id' => 'b'
		));

		$acl->allow('bbcode_use', array(
			'in'        => 'sig',
			'bbcode_id' => 'url'
		));

		$acl->allow('bbcode_use', array(
			'in'        => 'pm',
			'bbcode_id' => 'i'
		));

		$this->assertTrue($acl->isAllowed('bbcode_use', $acl->wildcard()));
		$this->assertTrue($acl->isAllowed('bbcode_use', array(
			'in'        => 'sig',
			'bbcode_id' => $acl->wildcard()
		)));
		$this->assertTrue($acl->isAllowed('bbcode_use', array(
			'in'       => 'forum',
			'forum_id' => $acl->wildcard()
		)));
	}

	public function testWildcardOnUnknownScopeDefaultsToGlobal()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));
		$acl->allow('bar');

		$this->assertFalse($acl->isAllowed('foo', array('quux' => $acl->wildcard())));
		$this->assertTrue($acl->isAllowed('bar', array('quux' => $acl->wildcard())));
	}

	public function testWildcardUsedAsAScopeKeyWithANormalScopeValue()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$this->assertTrue($acl->isAllowed('foo', array(
			'bar' => 123,
			$acl->wildcard() => 'xyz'
		)));
	}

	public function testWildcardUsedAsAScopeKeyWithAWildcardScopeValue()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$this->assertTrue($acl->isAllowed('foo', array(
			'bar' => 123,
			$acl->wildcard() => $acl->wildcard()
		)));

		$this->assertFalse($acl->isAllowed('foo', array(
			'bar' => 123
		)));

		$this->assertFalse($acl->isAllowed('foo', array(
			'bar' => 456,
			$acl->wildcard() => $acl->wildcard()
		)));
	}
}