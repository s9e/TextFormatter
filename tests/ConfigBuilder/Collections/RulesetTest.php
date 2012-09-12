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
	* @testdox disallowAtRoot() accepts a boolean
	*/
	public function testDisallowAtRootValid()
	{
		$ruleset = new Ruleset;
		$ruleset->disallowAtRoot(true);
	}

	/**
	* @testdox disallowAtRoot() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage disallowAtRoot() expects a boolean
	*/
	public function testDisallowAtRootInalid()
	{
		$ruleset = new Ruleset;
		$ruleset->disallowAtRoot('foo');
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
	* @testdox inheritChildRules() accepts a boolean
	*/
	public function testInheritChildRulesValid()
	{
		$ruleset = new Ruleset;
		$ruleset->inheritChildRules(true);
	}

	/**
	* @testdox inheritChildRules() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage inheritChildRules() expects a boolean
	*/
	public function testInheritChildRulesInalid()
	{
		$ruleset = new Ruleset;
		$ruleset->inheritChildRules('foo');
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







	/**
	* @testdox merge() accepts a 2D array of rules
	*/
	public function testMergeArray()
	{
		$rules = array(
			'allowChild' => array('B'),
			'denyChild'  => array('I')
		);

		$ruleset = new Ruleset;
		$ruleset->merge($rules);

		$this->assertEquals($rules, iterator_to_array($ruleset));
	}

	/**
	* @testdox merge() correctly copies the disallowAtRoot setting from an array
	*/
	public function testMergeArrayDisallowAtRoot()
	{
		$rules = array(
			'allowChild'     => array('B'),
			'disallowAtRoot' => true
		);

		$ruleset = new Ruleset;
		$ruleset->merge($rules);

		$this->assertEquals($rules, iterator_to_array($ruleset));
	}

	/**
	* @testdox merge() correctly copies the inheritChildRules setting from an array
	*/
	public function testMergeArrayInheritChildRules()
	{
		$rules = array(
			'allowChild'        => array('B'),
			'inheritChildRules' => true
		);

		$ruleset = new Ruleset;
		$ruleset->merge($rules);

		$this->assertEquals($rules, iterator_to_array($ruleset));
	}

	/**
	* @testdox merge() accepts an instance of Ruleset to copy its content
	*/
	public function testMergeInstanceOfRuleset()
	{
		$ruleset1 = new Ruleset;
		$ruleset1->allowChild('B');

		$ruleset2 = new Ruleset;
		$ruleset2->merge($ruleset1);

		$this->assertEquals($ruleset1, $ruleset2);
	}

	/**
	* @testdox merge() correctly copies the disallowAtRoot setting from an instance of Ruleset
	*/
	public function testMergeInstanceOfRulesetDisallowAtRoot()
	{
		$ruleset1 = new Ruleset;
		$ruleset1->disallowAtRoot(true);

		$ruleset2 = new Ruleset;
		$ruleset2->merge($ruleset1);

		$this->assertEquals($ruleset1, $ruleset2);
	}

	/**
	* @testdox merge() correctly copies the inheritChildRules setting from an instance of Ruleset
	*/
	public function testMergeInstanceOfRulesetInheritChildRules()
	{
		$ruleset1 = new Ruleset;
		$ruleset1->inheritChildRules(true);

		$ruleset2 = new Ruleset;
		$ruleset2->merge($ruleset1);

		$this->assertEquals($ruleset1, $ruleset2);
	}

	/**
	* @testdox merge() throws an InvalidArgumentException if its argument is not an array or an instance of Ruleset
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage merge() expects an array or an instance of Ruleset
	*/
	public function testSetRulesInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->merge(false);
	}
}