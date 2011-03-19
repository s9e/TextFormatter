<?php

namespace s9e\Toolkit\Acl\Tests;

use s9e\Toolkit\Acl\Acl;
use s9e\Toolkit\Acl\Wildcard;

include_once __DIR__ . '/../../src/Acl/Acl.php';
include_once __DIR__ . '/../../src/Acl/Wildcard.php';

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

		$this->assertTrue($acl->isAllowed('foo', new Wildcard));
	}

	public function test2DWildcardOn2DAllow()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$this->assertTrue($acl->isAllowed('foo', array('bar' => new Wildcard, 'baz' => 'xyz')));
		$this->assertTrue($acl->isAllowed('foo', array('bar' => 123, 'baz' => new Wildcard)));
	}

	public function test1DWildcardOn2DAllow()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$this->assertFalse($acl->isAllowed('foo', array('bar' => new Wildcard)));
		$this->assertFalse($acl->isAllowed('foo', array('baz' => new Wildcard)));
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

		$this->assertTrue($acl->isAllowed('bbcode_use', new Wildcard));
		$this->assertTrue($acl->isAllowed('bbcode_use', array(
			'in'        => 'sig',
			'bbcode_id' => new Wildcard
		)));
		$this->assertTrue($acl->isAllowed('bbcode_use', array(
			'in'       => 'forum',
			'forum_id' => new Wildcard
		)));
	}

	public function testWildcardOnUnknownScopeDefaultsToGlobal()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));
		$acl->allow('bar');

		$this->assertFalse($acl->isAllowed('foo', array('quux' => new Wildcard)));
		$this->assertTrue($acl->isAllowed('bar', array('quux' => new Wildcard)));
	}
}