<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
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
	* @testdox unset($ruleset['allowChild']) removes all allowChild rules but doesn't touch the rest
	*/
	public function testOffsetUnset()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('FOO');
		$ruleset->denyChild('BAR');

		unset($ruleset['allowChild']);

		$this->assertFalse(isset($ruleset['allowChild']));
		$this->assertTrue(isset($ruleset['denyChild']));
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
	* @testdox remove('allowChild') removes only 'allowChild' rules
	*/
	public function testRemoveAll()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('FOO');
		$ruleset->requireParent('BAR');
		$ruleset->remove('allowChild');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'requireParent'         => ['BAR']
			],
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

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'denyChild'             => ['FOO']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox remove('defaultChildRule') throws an exception
	* @expectedException RuntimeException defaultChildRule
	*/
	public function testRemoveDefaultChildRule()
	{
		$ruleset = new Ruleset;
		$ruleset->remove('defaultChildRule');
	}

	/**
	* @testdox remove('defaultDescendantRule') throws an exception
	* @expectedException RuntimeException defaultDescendantRule
	*/
	public function testRemoveDefaultDescendantRule()
	{
		$ruleset = new Ruleset;
		$ruleset->remove('defaultDescendantRule');
	}

	/**
	* @testdox remove('denyChild', 'IMG') unsets the denyChild list in the ruleset if there is no denyChild rules left
	*/
	public function testRemoveUnsets()
	{
		$ruleset = new Ruleset;
		$ruleset->denyChild('IMG');
		$ruleset->remove('denyChild', 'IMG');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow'
			],
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

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'denyChild'             => ['FOO', 'BAR']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox remove('denyChild', 'img') normalizes tag names
	*/
	public function testRemoveNormalizesTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->denyChild('FOO');
		$ruleset->denyChild('IMG');
		$ruleset->denyChild('IMG');
		$ruleset->remove('denyChild', 'img');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'denyChild'             => ['FOO']
			],
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
	* @testdox allowChild() normalizes tag names
	*/
	public function testAllowChildNormalizesTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('b');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'allowChild'            => ['B']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox allowChild() is chainable
	*/
	public function testAllowChildChainable()
	{
		$ruleset = new Ruleset;

		$this->assertSame($ruleset, $ruleset->allowChild('B'));
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
	* @testdox allowDescendant() normalizes tag names
	*/
	public function testAllowDescendantNormalizesTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->allowDescendant('b');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'allowDescendant'       => ['B']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox allowDescendant() is chainable
	*/
	public function testAllowDescendantChainable()
	{
		$ruleset = new Ruleset;

		$this->assertSame($ruleset, $ruleset->allowDescendant('B'));
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
	* @testdox autoClose() is chainable
	*/
	public function testAutoCloseChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->autoClose());
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
	* @testdox autoReopen() is chainable
	*/
	public function testAutoReopenChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->autoReopen());
	}

	/**
	* @testdox breakParagraph() accepts a boolean
	*/
	public function testBreakParagraphValid()
	{
		$ruleset = new Ruleset;
		$ruleset->breakParagraph(true);
	}

	/**
	* @testdox breakParagraph() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage breakParagraph() expects a boolean
	*/
	public function testBreakParagraphInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->breakParagraph('foo');
	}

	/**
	* @testdox breakParagraph() is chainable
	*/
	public function testBreakParagraphChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->breakParagraph());
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
	* @testdox closeAncestor() normalizes tag names
	*/
	public function testCloseAncestorNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->closeAncestor('b');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'closeAncestor'         => ['B']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox closeParent() normalizes tag names
	*/
	public function testCloseParentNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->closeParent('b');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'closeParent'           => ['B']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox closeParent() is chainable
	*/
	public function testCloseParentChainable()
	{
		$ruleset = new Ruleset;

		$this->assertSame($ruleset, $ruleset->closeParent('B'));
	}

	/**
	* @testdox createChild() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo#bar'
	*/
	public function testCreateChildInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->createChild('foo#bar');
	}

	/**
	* @testdox createChild() normalizes tag names
	*/
	public function testCreateChildNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->createChild('b');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'createChild'           => ['B']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox createChild() is chainable
	*/
	public function testCreateChildChainable()
	{
		$ruleset = new Ruleset;

		$this->assertSame($ruleset, $ruleset->createChild('B'));
	}

	/**
	* @testdox createParagraphs() accepts a boolean
	*/
	public function testCreateParagraphsValid()
	{
		$ruleset = new Ruleset;
		$ruleset->createParagraphs(true);
	}

	/**
	* @testdox createParagraphs() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage createParagraphs() expects a boolean
	*/
	public function testCreateParagraphsInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->createParagraphs('foo');
	}

	/**
	* @testdox createParagraphs() is chainable
	*/
	public function testCreateParagraphsChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->createParagraphs());
	}

	/**
	* @testdox defaultChildRule() accepts 'allow'
	*/
	public function testDefaultChildRuleAllow()
	{
		$ruleset = new Ruleset;
		$ruleset->defaultChildRule('allow');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow'
			],
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

		$this->assertEquals(
			[
				'defaultChildRule'      => 'deny',
				'defaultDescendantRule' => 'allow'
			],
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

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow'
			],
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

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'deny'
			],
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
	* @testdox ignoreTags() accepts a boolean
	*/
	public function testIgnoreTagsValid()
	{
		$ruleset = new Ruleset;
		$ruleset->ignoreTags(true);
	}

	/**
	* @testdox ignoreTags() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage ignoreTags() expects a boolean
	*/
	public function testIgnoreTagsInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->ignoreTags('foo');
	}

	/**
	* @testdox ignoreTags() is chainable
	*/
	public function testIgnoreTagsChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->ignoreTags());
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
	* @testdox denyChild() normalizes tag names
	*/
	public function testDenyChildNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->denyChild('b');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'denyChild'             => ['B']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox denyChild() is chainable
	*/
	public function testDenyChildChainable()
	{
		$ruleset = new Ruleset;

		$this->assertSame($ruleset, $ruleset->denyChild('B'));
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
	* @testdox denyDescendant() normalizes tag names
	*/
	public function testDenyDescendantNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->denyDescendant('b');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'denyDescendant'        => ['B']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox denyDescendant() is chainable
	*/
	public function testDenyDescendantChainable()
	{
		$ruleset = new Ruleset;

		$this->assertSame($ruleset, $ruleset->denyDescendant('B'));
	}

	/**
	* @testdox disableAutoLineBreaks() accepts a boolean
	*/
	public function testDisableAutoLineBreaksValid()
	{
		$ruleset = new Ruleset;
		$ruleset->disableAutoLineBreaks(true);
	}

	/**
	* @testdox disableAutoLineBreaks() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage disableAutoLineBreaks() expects a boolean
	*/
	public function testDisableAutoLineBreaksInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->disableAutoLineBreaks('foo');
	}

	/**
	* @testdox disableAutoLineBreaks() is chainable
	*/
	public function testDisableAutoLineBreaksChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->disableAutoLineBreaks());
	}

	/**
	* @testdox enableAutoLineBreaks() accepts a boolean
	*/
	public function testEnableAutoLineBreaksValid()
	{
		$ruleset = new Ruleset;
		$ruleset->enableAutoLineBreaks(true);
	}

	/**
	* @testdox enableAutoLineBreaks() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage enableAutoLineBreaks() expects a boolean
	*/
	public function testEnableAutoLineBreaksInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->enableAutoLineBreaks('foo');
	}

	/**
	* @testdox enableAutoLineBreaks() is chainable
	*/
	public function testEnableAutoLineBreaksChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->enableAutoLineBreaks());
	}

	/**
	* @testdox fosterParent() throws an exception on invalid tag name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid tag name 'foo#bar'
	*/
	public function testFosterParentInvalidTagName()
	{
		$ruleset = new Ruleset;
		$ruleset->fosterParent('foo#bar');
	}

	/**
	* @testdox fosterParent() normalizes tag names
	*/
	public function testFosterParentNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->fosterParent('b');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'fosterParent'          => ['B']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox fosterParent() is chainable
	*/
	public function testFosterParentChainable()
	{
		$ruleset = new Ruleset;

		$this->assertSame($ruleset, $ruleset->fosterParent('B'));
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
	* @testdox ignoreText() is chainable
	*/
	public function testIgnoreTextChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->ignoreText());
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
	* @testdox isTransparent() is chainable
	*/
	public function testIsTransparentChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->isTransparent());
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
	* @testdox ignoreSurroundingWhitespace() is chainable
	*/
	public function testIgnoreSurroundingWhitespaceChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->ignoreSurroundingWhitespace());
	}

	/**
	* @testdox preventLineBreaks() accepts a boolean
	*/
	public function testPreventLineBreaksValid()
	{
		$ruleset = new Ruleset;
		$ruleset->preventLineBreaks(true);
	}

	/**
	* @testdox preventLineBreaks() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage preventLineBreaks() expects a boolean
	*/
	public function testPreventLineBreaksInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->preventLineBreaks('foo');
	}

	/**
	* @testdox preventLineBreaks() is chainable
	*/
	public function testPreventLineBreaksChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->preventLineBreaks());
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
	* @testdox requireAncestor() normalizes tag names
	*/
	public function testRequireAncestorNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->requireAncestor('b');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'requireAncestor'       => ['B']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox requireAncestor() is chainable
	*/
	public function testRequireAncestorChainable()
	{
		$ruleset = new Ruleset;

		$this->assertSame($ruleset, $ruleset->requireAncestor('B'));
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
	* @testdox requireParent() normalizes tag names
	*/
	public function testRequireParentNormalizesTagName()
	{
		$ruleset = new Ruleset;

		$ruleset->requireParent('b');

		$this->assertEquals(
			[
				'defaultChildRule'      => 'allow',
				'defaultDescendantRule' => 'allow',
				'requireParent'         => ['B']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox requireParent() is chainable
	*/
	public function testRequireParentChainable()
	{
		$ruleset = new Ruleset;

		$this->assertSame($ruleset, $ruleset->requireParent('B'));
	}

	/**
	* @testdox suspendAutoLineBreaks() accepts a boolean
	*/
	public function testSuspendAutoLineBreaksValid()
	{
		$ruleset = new Ruleset;
		$ruleset->suspendAutoLineBreaks(true);
	}

	/**
	* @testdox suspendAutoLineBreaks() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage suspendAutoLineBreaks() expects a boolean
	*/
	public function testSuspendAutoLineBreaksInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->suspendAutoLineBreaks('foo');
	}

	/**
	* @testdox suspendAutoLineBreaks() is chainable
	*/
	public function testSuspendAutoLineBreaksChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->suspendAutoLineBreaks());
	}

	/**
	* @testdox trimFirstLine() accepts a boolean
	*/
	public function testTrimWhitespaceValid()
	{
		$ruleset = new Ruleset;
		$ruleset->trimFirstLine(true);
	}

	/**
	* @testdox trimFirstLine() throws an exception if its argument is not a boolean
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage trimFirstLine() expects a boolean
	*/
	public function testTrimWhitespaceInvalid()
	{
		$ruleset = new Ruleset;
		$ruleset->trimFirstLine('foo');
	}

	/**
	* @testdox trimFirstLine() is chainable
	*/
	public function testTrimWhitespaceChainable()
	{
		$ruleset = new Ruleset;
		$this->assertSame($ruleset, $ruleset->trimFirstLine());
	}

	/**
	* @testdox merge() accepts a 2D array of rules
	*/
	public function testMergeArray()
	{
		$rules = [
			'defaultChildRule'      => 'allow',
			'defaultDescendantRule' => 'allow',
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
			'defaultChildRule'      => 'deny',
			'defaultDescendantRule' => 'allow',
			'allowChild'            => ['B']
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
			'defaultChildRule'      => 'allow',
			'defaultDescendantRule' => 'deny',
			'allowDescendant'       => ['B']
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
			'defaultChildRule'      => 'allow',
			'defaultDescendantRule' => 'allow',
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
	* @testdox merge() overwrites boolean rules by default
	*/
	public function testMergeOverwrite()
	{
		$ruleset1 = new Ruleset;
		$ruleset1->isTransparent(true);

		$ruleset2 = new Ruleset;
		$ruleset2->isTransparent(false);
		$ruleset2->merge($ruleset1);

		$this->assertEquals($ruleset1, $ruleset2);
	}

	/**
	* @testdox merge() does not overwrite boolean rules if its second argument is FALSE
	*/
	public function testMergeNoOverwrite()
	{
		$ruleset1 = new Ruleset;
		$ruleset1->isTransparent(true);

		$ruleset2 = new Ruleset;
		$ruleset2->isTransparent(false);
		$ruleset2->merge($ruleset1, false);

		$this->assertNotEquals($ruleset1, $ruleset2);

		$ruleset1->isTransparent(false);
		$this->assertEquals($ruleset1, $ruleset2);
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
			'ignoreTags'               => true,
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
		ConfigHelper::filterVariants($config);

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
		ConfigHelper::filterVariants($config);

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
			'autoClose'                   => Parser::RULE_AUTO_CLOSE,
			'autoReopen'                  => Parser::RULE_AUTO_REOPEN,
			'breakParagraph'              => Parser::RULE_BREAK_PARAGRAPH,
			'createParagraphs'            => Parser::RULE_CREATE_PARAGRAPHS,
			'disableAutoLineBreaks'       => Parser::RULE_DISABLE_AUTO_BR,
			'enableAutoLineBreaks'        => Parser::RULE_ENABLE_AUTO_BR,
			'ignoreSurroundingWhitespace' => Parser::RULE_IGNORE_WHITESPACE,
			'ignoreTags'                  => Parser::RULE_IGNORE_TAGS,
			'ignoreText'                  => Parser::RULE_IGNORE_TEXT,
			'isTransparent'               => Parser::RULE_IS_TRANSPARENT,
			'preventLineBreaks'           => Parser::RULE_PREVENT_BR,
			'suspendAutoLineBreaks'       => Parser::RULE_SUSPEND_AUTO_BR,
			'trimFirstLine'               => Parser::RULE_TRIM_FIRST_LINE
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

		$this->assertSame(Parser::RULE_AUTO_CLOSE | Parser::RULE_IGNORE_WHITESPACE, $config['flags']);
	}
}