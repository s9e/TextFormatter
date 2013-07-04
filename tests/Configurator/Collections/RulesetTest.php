<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Tests\Test;

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
			['FOO'],
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
			[],
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
			['requireParent' => ['BAR']],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox remove('denyChild', 'IMG') removes all denyChild rules targeting IMG
	*/
	public function testRemoveTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->denyChild('FOO');
		$ruleset->denyChild('IMG');
		$ruleset->denyChild('IMG');
		$ruleset->remove('denyChild', 'IMG');

		$this->assertSame(
			['denyChild' => ['FOO']],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox remove('denyChild') unsets the denyChild list in the ruleset
	*/
	public function testRemoveAll()
	{
		$ruleset = new Ruleset;
		$ruleset->denyChild('FOO');
		$ruleset->denyChild('IMG');
		$ruleset->denyChild('IMG');
		$ruleset->remove('denyChild');

		$this->assertSame(
			[],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox remove('denyChild', 'IMG') unsets the denyChild list in the ruleset if there is no denyChild rules left
	*/
	public function testRemoveUnsets()
	{
		$ruleset = new Ruleset;
		$ruleset->denyChild('IMG');
		$ruleset->remove('denyChild', 'IMG');

		$this->assertSame(
			[],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox remove('denyChild', 'IMG') rearranges keys to remove gaps
	*/
	public function testRemoveTagNameRearrange()
	{
		$ruleset = new Ruleset;
		$ruleset->denyChild('FOO');
		$ruleset->denyChild('IMG');
		$ruleset->denyChild('IMG');
		$ruleset->denyChild('BAR');
		$ruleset->remove('denyChild', 'IMG');

		$this->assertSame(
			['denyChild' => ['FOO', 'BAR']],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox remove('denyChild', 'img') normalizes tag name
	*/
	public function testRemoveNormalizesTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->denyChild('FOO');
		$ruleset->denyChild('IMG');
		$ruleset->denyChild('IMG');
		$ruleset->remove('denyChild', 'img');

		$this->assertSame(
			['denyChild' => ['FOO']],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox allowChild() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo#bar'
	*/
	public function testAllowChildInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('foo#bar');
	}

	/**
	* @testdox allowChild() normalizes tag name
	*/
	public function testAllowChildNormalizesTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('b');

		$this->assertSame(
			['allowChild' => ['B']],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox allowDescendant() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo#bar'
	*/
	public function testAllowDescendantInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->allowDescendant('foo#bar');
	}

	/**
	* @testdox allowDescendant() normalizes tag name
	*/
	public function testAllowDescendantNormalizesTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->allowDescendant('b');

		$this->assertSame(
			['allowDescendant' => ['B']],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox autoClose() accepts a boolean
	*/
	public function testAutoCloseValid()
	{
		$ruleset = new Ruleset;
		$ruleset->autoClose(true);
	}

	/**
	* @testdox autoClose() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage autoClose() expects a boolean
	*/
	public function testAutoCloseInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->autoClose('foo');
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
	* @expectedExceptionMessage Invalid tag name 'foo#bar'
	*/
	public function testCloseAncestorInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->closeAncestor('foo#bar');
	}

	/**
	* @testdox closeAncestor() normalizes tag name
	*/
	public function testCloseAncestorNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->closeAncestor('b');

		$this->assertSame(
			['closeAncestor' => ['B']],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox closeParent() normalizes tag name
	*/
	public function testCloseParentNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->closeParent('b');

		$this->assertSame(
			['closeParent' => ['B']],
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
			['defaultChildRule' => 'allow'],
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
			['defaultChildRule' => 'deny'],
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
			['defaultDescendantRule' => 'allow'],
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
			['defaultDescendantRule' => 'deny'],
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
	* @expectedExceptionMessage Invalid tag name 'foo#bar'
	*/
	public function testDenyChildInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->denyChild('foo#bar');
	}

	/**
	* @testdox denyChild() normalizes tag name
	*/
	public function testDenyChildNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->denyChild('b');

		$this->assertSame(
			['denyChild' => ['B']],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox denyDescendant() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo#bar'
	*/
	public function testDenyDescendantInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->denyDescendant('foo#bar');
	}

	/**
	* @testdox denyDescendant() normalizes tag name
	*/
	public function testDenyDescendantNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->denyDescendant('b');

		$this->assertSame(
			['denyDescendant' => ['B']],
			iterator_to_array($ruleset)
		);
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
	* @testdox noBrChild() accepts a boolean
	*/
	public function testNoBrChildValid()
	{
		$ruleset = new Ruleset;
		$ruleset->noBrChild(true);
	}

	/**
	* @testdox noBrChild() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage noBrChild() expects a boolean
	*/
	public function testNoBrChildInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->noBrChild('foo');
	}

	/**
	* @testdox noBrDescendant() accepts a boolean
	*/
	public function testNoBrDescendantValid()
	{
		$ruleset = new Ruleset;
		$ruleset->noBrDescendant(true);
	}

	/**
	* @testdox noBrDescendant() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage noBrDescendant() expects a boolean
	*/
	public function testNoBrDescendantInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->noBrDescendant('foo');
	}

	/**
	* @testdox ignoreSurroundingWhitespace() accepts a boolean
	*/
	public function testIgnoreSurroundingWhitespaceValid()
	{
		$ruleset = new Ruleset;
		$ruleset->ignoreSurroundingWhitespace(true);
	}

	/**
	* @testdox ignoreSurroundingWhitespace() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage ignoreSurroundingWhitespace() expects a boolean
	*/
	public function testIgnoreSurroundingWhitespaceInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->ignoreSurroundingWhitespace('foo');
	}

	/**
	* @testdox requireParent() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo#bar'
	*/
	public function testRequireParentInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->requireParent('foo#bar');
	}

	/**
	* @testdox requireParent() normalizes tag name
	*/
	public function testRequireParentNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->requireParent('b');

		$this->assertSame(
			['requireParent' => ['B']],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox requireAncestor() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo#bar'
	*/
	public function testRequireAncestorInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->requireAncestor('foo#bar');
	}

	/**
	* @testdox requireAncestor() normalizes tag name
	*/
	public function testRequireAncestorNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->requireAncestor('b');

		$this->assertSame(
			['requireAncestor' => ['B']],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox merge() accepts a 2D array of rules
	*/
	public function testMergeArray()
	{
		$rules = [
			'allowChild' => ['B'],
			'denyChild'  => ['I']
		];

		$ruleset = new Ruleset;
		$ruleset->merge($rules);

		$this->assertEquals($rules, iterator_to_array($ruleset));
	}

	/**
	* @testdox merge() correctly copies the defaultChildRule setting from an array
	*/
	public function testMergeArrayDefaultChildRule()
	{
		$rules = [
			'allowChild'       => ['B'],
			'defaultChildRule' => 'allow'
		];

		$ruleset = new Ruleset;
		$ruleset->merge($rules);

		$this->assertEquals($rules, iterator_to_array($ruleset));
	}

	/**
	* @testdox merge() correctly copies the defaultDescendantRule setting from an array
	*/
	public function testMergeArrayDefaultDescendantRule()
	{
		$rules = [
			'allowDescendant'       => ['B'],
			'defaultDescendantRule' => 'allow'
		];

		$ruleset = new Ruleset;
		$ruleset->merge($rules);

		$this->assertEquals($rules, iterator_to_array($ruleset));
	}

	/**
	* @testdox merge() correctly copies the isTransparent setting from an array
	*/
	public function testMergeArrayIsTransparent()
	{
		$rules = [
			'allowChild'    => ['B'],
			'isTransparent' => true
		];

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
		$rules = [
			'allowChild'            => 'X',
			'allowDescendant'       => 'X',
			'defaultChildRule'      => 'deny',
			'defaultDescendantRule' => 'allow',
			'denyAll'               => true,
			'denyChild'             => 'X',
			'denyDescendant'        => 'X',
			'isTransparent'         => false,
			'requireParent'         => 'X'
		];

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
	* @testdox asConfig() uses target names as keys for closeAncestor
	*/
	public function testAsConfigFlipsCloseAncestor()
	{
		$ruleset = new Ruleset;

		$ruleset->closeAncestor('X');
		$ruleset->closeAncestor('Y');

		$config = $ruleset->asConfig();

		$this->assertArrayHasKey('closeAncestor', $config);
		$this->assertArrayHasKey('X', $config['closeAncestor']);
		$this->assertArrayHasKey('Y', $config['closeAncestor']);
	}

	/**
	* @testdox asConfig() uses target names as keys for closeParent
	*/
	public function testAsConfigFlipsCloseParent()
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
	* @testdox asConfig() uses target names as keys for requireAncestor
	*/
	public function testAsConfigDoesNotFlipRequireAncestor()
	{
		$ruleset = new Ruleset;

		$ruleset->requireAncestor('X');
		$ruleset->requireAncestor('Y');

		$config = $ruleset->asConfig();

		$this->assertArrayHasKey('requireAncestor', $config);
		$this->assertEquals(['X', 'Y'], $config['requireAncestor']);
	}

	/**
	* @testdox asConfig() packs boolean rules in a value named "flags"
	*/
	public function testAsConfigBitfield()
	{
		$booleanRules = [
			'autoClose'      => Parser::RULE_AUTO_CLOSE,
			'autoReopen'     => Parser::RULE_AUTO_REOPEN,
			'ignoreSurroundingWhitespace' => Parser::RULE_TRIM_WHITESPACE,
			'ignoreText'     => Parser::RULE_IGNORE_TEXT,
			'isTransparent'  => Parser::RULE_IS_TRANSPARENT,
			'noBrChild'      => Parser::RULE_NO_BR_CHILD,
			'noBrDescendant' => Parser::RULE_NO_BR_DESCENDANT | Parser::RULE_NO_BR_CHILD
		];

		$ruleset = new Ruleset;
		foreach ($booleanRules as $methodName => $bitValue)
		{
			$ruleset->clear();
			$ruleset->$methodName();

			$config = $ruleset->asConfig();

			$this->assertArrayHasKey('flags', $config);
			$this->assertSame($bitValue, $config['flags']);
		}
	}

	/**
	* @testdox asConfig() can pack multiple boolean rules in a value named "flags"
	*/
	public function testAsConfigBitfieldMultiple()
	{
		$ruleset = new Ruleset;
		$ruleset->autoClose();
		$ruleset->ignoreSurroundingWhitespace();

		$config = $ruleset->asConfig();

		$this->assertSame(Parser::RULE_AUTO_CLOSE | Parser::RULE_TRIM_WHITESPACE, $config['flags']);
	}

	/**
	* @testdox asConfig() sets noBrChild's bit if noBrDescendant is set
	*/
	public function testAsConfigNoBrDescendantCascadesOnNoBrChild()
	{
		$ruleset = new Ruleset;
		$ruleset->noBrDescendant();

		$config = $ruleset->asConfig();

		$this->assertSame(Parser::RULE_NO_BR_CHILD | Parser::RULE_NO_BR_DESCENDANT, $config['flags']);
	}
}