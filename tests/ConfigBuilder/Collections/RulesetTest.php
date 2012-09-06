<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\Ruleset;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Collections\Ruleset
*/
class RulesetTest extends Test
{
	/**
	* @testdox clear() removes all rules
	*/
	public function testClearAll()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('foo');
		$ruleset->clear();

		$this->assertSame(
			array(),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox clear('allowChild') removes only 'allowChild' rules
	*/
	public function testClearSome()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('FOO');
		$ruleset->requireParent('BAR');
		$ruleset->clear('allowChild');

		$this->assertSame(
			array('requireParent' => array('BAR')),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox allowChild() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo-bar'
	*/
	public function testAllowChildInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('foo-bar');
	}

	/**
	* @testdox allowChild() normalizes tag name
	*/
	public function testAllowChildNormalizesTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('b');

		$this->assertSame(
			array('allowChild' => array('B')),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox allowDescendant() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo-bar'
	*/
	public function testAllowDescendantInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->allowDescendant('foo-bar');
	}

	/**
	* @testdox allowDescendant() normalizes tag name
	*/
	public function testAllowDescendantNormalizesTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->allowDescendant('b');

		$this->assertSame(
			array('allowDescendant' => array('B')),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox closeAncestor() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo-bar'
	*/
	public function testCloseAncestorInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->closeAncestor('foo-bar');
	}

	/**
	* @testdox closeAncestor() normalizes tag name
	*/
	public function testCloseAncestorNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->closeAncestor('b');

		$this->assertSame(
			array('closeAncestor' => array('B')),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox closeParent() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo-bar'
	*/
	public function testCloseParentInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->closeParent('foo-bar');
	}

	/**
	* @testdox closeParent() normalizes tag name
	*/
	public function testCloseParentNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->closeParent('b');

		$this->assertSame(
			array('closeParent' => array('B')),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox denyChild() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo-bar'
	*/
	public function testDenyChildInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->denyChild('foo-bar');
	}

	/**
	* @testdox denyChild() normalizes tag name
	*/
	public function testDenyChildNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->denyChild('b');

		$this->assertSame(
			array('denyChild' => array('B')),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox denyDescendant() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo-bar'
	*/
	public function testDenyDescendantInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->denyDescendant('foo-bar');
	}

	/**
	* @testdox denyDescendant() normalizes tag name
	*/
	public function testDenyDescendantNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->denyDescendant('b');

		$this->assertSame(
			array('denyDescendant' => array('B')),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox reopenChild() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo-bar'
	*/
	public function testReopenChildInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->reopenChild('foo-bar');
	}

	/**
	* @testdox reopenChild() normalizes tag name
	*/
	public function testReopenChildNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->reopenChild('b');

		$this->assertSame(
			array('reopenChild' => array('B')),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox requireParent() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo-bar'
	*/
	public function testRequireParentInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->requireParent('foo-bar');
	}

	/**
	* @testdox requireParent() normalizes tag name
	*/
	public function testRequireParentNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->requireParent('b');

		$this->assertSame(
			array('requireParent' => array('B')),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox requireAncestor() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo-bar'
	*/
	public function testRequireAncestorInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->requireAncestor('foo-bar');
	}

	/**
	* @testdox requireAncestor() normalizes tag name
	*/
	public function testRequireAncestorNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->requireAncestor('b');

		$this->assertSame(
			array('requireAncestor' => array('B')),
			iterator_to_array($ruleset)
		);
	}
}