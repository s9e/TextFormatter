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

		$this->assertFalse($acl->getReader()->isAllowed('foo', array('bar' => 123)));
	}

	public function testLocalDenyOverridesGlobalAllow()
	{
		$acl = new Acl;
		$acl->allow('foo');
		$acl->deny('foo', array('bar' => 123));

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('foo', array('bar' => 123)));
	}

	public function testUnknownScopeDefaultsToGlobalScope()
	{
		$acl = new Acl;
		$acl->allow('foo');

		$this->assertTrue($acl->getReader()->isAllowed('foo', array('bar' => 123)));
	}

	public function test2DInheritsFrom1D()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));
		$acl->allow('foo', array('bar' => 456));

		$reader = $acl->getReader();

		$this->assertFalse($reader->isAllowed('foo', array('bar' => 123)));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 123, 'baz' => 'xyz')));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 456)));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 456, 'baz' => 'xyz')));
	}

	public function testGlobalAnyOn2DAllow()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('foo', $reader->any()));
	}

	public function test2DAnyOn2DAllow()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('foo', array('bar' => $reader->any(), 'baz' => 'xyz')));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 123, 'baz' => $reader->any())));
	}

	public function test1DAnyOn2DAllow()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$reader = $acl->getReader();

		$this->assertFalse($reader->isAllowed('foo', array('bar' => $reader->any())));
		$this->assertFalse($reader->isAllowed('foo', array('baz' => $reader->any())));
	}

	/**
	* Regression test for a bug where the "any" bits are generated in the wrong order because
	* dimensions are not in alphabetical order
	*/
	public function testAnyOn3DAllow()
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

		$reader = $acl->getReader();

		$this->assertTrue($reader->isAllowed('bbcode_use', $reader->any()));
		$this->assertTrue($reader->isAllowed('bbcode_use', array(
			'in'        => 'sig',
			'bbcode_id' => $reader->any()
		)));
		$this->assertTrue($reader->isAllowed('bbcode_use', array(
			'in'       => 'forum',
			'forum_id' => $reader->any()
		)));
	}

	public function testAnyOnUnknownScopeDefaultsToGlobal()
	{
		$acl = new Acl;
		$acl->allow('foo', array('bar' => 123, 'baz' => 'xyz'));
		$acl->allow('bar');

		$reader = $acl->getReader();

		$this->assertFalse($reader->isAllowed('foo', array('quux' => $reader->any())));
		$this->assertTrue($reader->isAllowed('bar', array('quux' => $reader->any())));
	}
}