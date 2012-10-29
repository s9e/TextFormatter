<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\Ruleset;

/**
* @covers s9e\TextFormatter\Configurator\Collections\Ruleset
*/
class RulesetTest extends Test
{
	/**
	* @testdox isset($ruleset['allowChild']) tests whether any allowChild rules exist
	*/
	public function testOffsetExists()
	{
		$ruleset = new Ruleset;
		$this->assertFalse(isset($ruleset['allowChild']));
		$ruleset->allowChild('FOO');
		$this->assertTrue(isset($ruleset['allowChild']));
	}

	/**
	* @testdox $ruleset['allowChild'] returns the allowChild rules if they exist
	*/
	public function testOffsetGet()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('FOO');

		$this->assertSame(
			array('FOO'),
			$ruleset['allowChild']
		);
	}

	/**
	* @testdox Trying to set rules via array access throws a RuntimeException
	* @expectedException RuntimeException
	* @expectedExceptionMessage Not supported
	*/
	public function testOffsetSet()
	{
		$ruleset = new Ruleset;
		$ruleset['allowChild'] = 'FOO';
	}

	/**
	* @testdox unset($ruleset['allowChild']) clears all allowChild rules
	*/
	public function testOffsetUnset()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('FOO');

		unset($ruleset['allowChild']);

		$this->assertFalse(isset($ruleset['allowChild']));
	}

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
	* @testdox autoReopen() accepts a boolean
	*/
	public function testAutoReopenValid()
	{
		$ruleset = new Ruleset;
		$ruleset->autoReopen(true);
	}

	/**
	* @testdox autoReopen() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage autoReopen() expects a boolean
	*/
	public function testAutoReopenInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->autoReopen('foo');
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
	* @testdox defaultChildRule() accepts 'allow'
	*/
	public function testDefaultChildRuleAllow()
	{
		$ruleset = new Ruleset;
		$ruleset->defaultChildRule('allow');

		$this->assertSame(
			array('defaultChildRule' => 'allow'),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox defaultChildRule() accepts 'deny'
	*/
	public function testDefaultChildRuleDeny()
	{
		$ruleset = new Ruleset;
		$ruleset->defaultChildRule('deny');

		$this->assertSame(
			array('defaultChildRule' => 'deny'),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox defaultChildRule() throws an exception if passed anything else than 'allow' or 'deny'
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage defaultChildRule() only accepts 'allow' or 'deny'
	*/
	public function testDefaultChildRuleInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->defaultChildRule('invalid');
	}

	/**
	* @testdox defaultDescendantRule() accepts 'allow'
	*/
	public function testDefaultDescendantRuleAllow()
	{
		$ruleset = new Ruleset;
		$ruleset->defaultDescendantRule('allow');

		$this->assertSame(
			array('defaultDescendantRule' => 'allow'),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox defaultDescendantRule() accepts 'deny'
	*/
	public function testDefaultDescendantRuleDeny()
	{
		$ruleset = new Ruleset;
		$ruleset->defaultDescendantRule('deny');

		$this->assertSame(
			array('defaultDescendantRule' => 'deny'),
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox defaultDescendantRule() throws an exception if passed anything else than 'allow' or 'deny'
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage defaultDescendantRule() only accepts 'allow' or 'deny'
	*/
	public function testDefaultDescendantRuleInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->defaultDescendantRule('invalid');
	}

	/**
	* @testdox denyAll() accepts a boolean
	*/
	public function testDenyAllValid()
	{
		$ruleset = new Ruleset;
		$ruleset->denyAll(true);
	}

	/**
	* @testdox denyAll() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage denyAll() expects a boolean
	*/
	public function testDenyAllInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->denyAll('foo');
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
	public function testDisallowAtRootInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->disallowAtRoot('foo');
	}

	/**
	* @testdox ignoreText() accepts a boolean
	*/
	public function testIgnoreTextValid()
	{
		$ruleset = new Ruleset;
		$ruleset->ignoreText(true);
	}

	/**
	* @testdox ignoreText() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage ignoreText() expects a boolean
	*/
	public function testIgnoreTextInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->ignoreText('foo');
	}

	/**
	* @testdox isTransparent() accepts a boolean
	*/
	public function testIsTransparentValid()
	{
		$ruleset = new Ruleset;
		$ruleset->isTransparent(true);
	}

	/**
	* @testdox isTransparent() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage isTransparent() expects a boolean
	*/
	public function testIsTransparentInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->isTransparent('foo');
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
	* @testdox merge() correctly copies the defaultChildRule setting from an array
	*/
	public function testMergeArrayDefaultChildRule()
	{
		$rules = array(
			'allowChild'       => array('B'),
			'defaultChildRule' => 'allow'
		);

		$ruleset = new Ruleset;
		$ruleset->merge($rules);

		$this->assertEquals($rules, iterator_to_array($ruleset));
	}

	/**
	* @testdox merge() correctly copies the defaultDescendantRule setting from an array
	*/
	public function testMergeArrayDefaultDescendantRule()
	{
		$rules = array(
			'allowDescendant'       => array('B'),
			'defaultDescendantRule' => 'allow'
		);

		$ruleset = new Ruleset;
		$ruleset->merge($rules);

		$this->assertEquals($rules, iterator_to_array($ruleset));
	}

	/**
	* @testdox merge() correctly copies the isTransparent setting from an array
	*/
	public function testMergeArrayIsTransparent()
	{
		$rules = array(
			'allowChild'   => array('B'),
			'isTransparent' => true
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
	* @testdox merge() correctly copies the defaultChildRule setting from an instance of Ruleset
	*/
	public function testMergeInstanceOfRulesetDefaultChildRule()
	{
		$ruleset1 = new Ruleset;
		$ruleset1->defaultChildRule('allow');

		$ruleset2 = new Ruleset;
		$ruleset2->merge($ruleset1);

		$this->assertEquals($ruleset1, $ruleset2);
	}

	/**
	* @testdox merge() correctly copies the defaultDescendantRule setting from an instance of Ruleset
	*/
	public function testMergeInstanceOfRulesetDefaultDescendantRule()
	{
		$ruleset1 = new Ruleset;
		$ruleset1->defaultDescendantRule('allow');

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
	* @testdox merge() correctly copies the isTransparent setting from an instance of Ruleset
	*/
	public function testMergeInstanceOfRulesetIsTransparent()
	{
		$ruleset1 = new Ruleset;
		$ruleset1->isTransparent(true);

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

	/**
	* @testdox asConfig() does not return rules that are not used during parsing
	*/
	public function testAsConfigOmitsUnneededRules()
	{
		$ruleset = new Ruleset;
		$rules = array(
			'allowChild'            => 'X',
			'allowDescendant'       => 'X',
			'defaultChildRule'      => 'deny',
			'defaultDescendantRule' => 'allow',
			'denyChild'             => 'X',
			'denyDescendant'        => 'X',
			'disallowAtRoot'        => true,
			'requireParent'         => 'X'
		);

		foreach ($rules as $k => $v)
		{
			$ruleset->$k($v);
		}

		$config = $ruleset->asConfig();

		foreach ($rules as $k => $v)
		{
			$this->assertArrayNotHasKey($k, $config);
		}
	}

	/**
	* @testdox asConfig() flips arrays to use target names as keys
	*/
	public function testAsConfigFlipsArrays()
	{
		$ruleset = new Ruleset;

		$ruleset->closeParent('X');
		$ruleset->closeParent('Y');

		$config = $ruleset->asConfig();

		$this->assertArrayHasKey('closeParent', $config);
		$this->assertArrayHasKey('X', $config['closeParent']);
		$this->assertArrayHasKey('Y', $config['closeParent']);
	}

	/**
	* @testdox asConfig() does not attempt to flip scalar rules such as "isTransparent"
	*/
	public function testAsConfigDoesNotFlipScalars()
	{
		$ruleset = new Ruleset;
		$ruleset->isTransparent(true);

		$this->assertSame(
			array('isTransparent' => true),
			$ruleset->asConfig()
		);
	}
}