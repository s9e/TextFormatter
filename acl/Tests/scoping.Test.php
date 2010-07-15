<?php

namespace s9e\toolkit\acl;

include_once __DIR__ . '/../builder.php';
include_once __DIR__ . '/../reader.php';

class testScoping extends \PHPUnit_Framework_TestCase
{
	public function testGlobalDenyOverridesLocalAllow()
	{
		$builder = new builder;
		$builder->deny('foo');
		$builder->allow('foo', array('bar' => 123));

		$this->assertFalse($builder->getReader()->isAllowed('foo', array('bar' => 123)));
	}

	public function testLocalDenyOverridesGlobalAllow()
	{
		$builder = new builder;
		$builder->allow('foo');
		$builder->deny('foo', array('bar' => 123));

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo'));
		$this->assertFalse($reader->isAllowed('foo', array('bar' => 123)));
	}

	public function testUnknownScopeDefaultsToGlobalScope()
	{
		$builder = new builder;
		$builder->allow('foo');

		$this->assertTrue($builder->getReader()->isAllowed('foo', array('bar' => 123)));
	}

	public function test2DInheritsFrom1D()
	{
		$builder = new builder;
		$builder->allow('foo', array('bar' => 123, 'baz' => 'xyz'));
		$builder->allow('foo', array('bar' => 456));

		$reader = $builder->getReader();

		$this->assertFalse($reader->isAllowed('foo', array('bar' => 123)));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 123, 'baz' => 'xyz')));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 456)));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 456, 'baz' => 'xyz')));
	}

	public function testGlobalAnyOn2DAllow()
	{
		$builder = new builder;
		$builder->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo', $reader->any));
	}

	public function test2DAnyOn2DAllow()
	{
		$builder = new builder;
		$builder->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('foo', array('bar' => $reader->any, 'baz' => 'xyz')));
		$this->assertTrue($reader->isAllowed('foo', array('bar' => 123, 'baz' => $reader->any)));
	}

	public function test1DAnyOn2DAllow()
	{
		$builder = new builder;
		$builder->allow('foo', array('bar' => 123, 'baz' => 'xyz'));

		$reader = $builder->getReader();

		$this->assertFalse($reader->isAllowed('foo', array('bar' => $reader->any)));
		$this->assertFalse($reader->isAllowed('foo', array('baz' => $reader->any)));
	}

	/**
	* Regression test for a bug where the "any" bits are generated in the wrong order because
	* dimensions are not in alphabetical order
	*/
	public function testAnyOn3DAllow()
	{
		$builder = new builder;
		$builder->allow('bbcode_use', array(
			'forum_id' => 3
		));

		$builder->allow('bbcode_use', array(
			'in'        => 'sig',
			'bbcode_id' => 'b'
		));

		$builder->allow('bbcode_use', array(
			'in'        => 'sig',
			'bbcode_id' => 'url'
		));

		$builder->allow('bbcode_use', array(
			'in'        => 'pm',
			'bbcode_id' => 'i'
		));

		$reader = $builder->getReader();

		$this->assertTrue($reader->isAllowed('bbcode_use', $reader->any));
		$this->assertTrue($reader->isAllowed('bbcode_use', array(
			'in'        => 'sig',
			'bbcode_id' => $reader->any
		)));
		$this->assertTrue($reader->isAllowed('bbcode_use', array(
			'in'       => 'forum',
			'forum_id' => $reader->any
		)));
	}

	public function testAnyOnUnknownScopeDefaultsToGlobal()
	{
		$builder = new builder;
		$builder->allow('foo', array('bar' => 123, 'baz' => 'xyz'));
		$builder->allow('bar');

		$reader = $builder->getReader();

		$this->assertFalse($reader->isAllowed('foo', array('quux' => $reader->any)));
		$this->assertTrue($reader->isAllowed('bar', array('quux' => $reader->any)));
	}
}