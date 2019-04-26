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
	* @testdox Setting an unknown rule throws an exception
	*/
	public function testUnknownRule()
	{
		$this->expectException('BadMethodCallException');
		$this->expectExceptionMessage("Undefined method 'bar'");

		$ruleset = new Ruleset;
		$ruleset->bar();
	}

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
	*/
	public function testOffsetSet()
	{
		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Not supported');

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
				'requireParent' => ['BAR']
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
				'denyChild' => ['FOO']
			],
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

		$this->assertEquals(
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

		$this->assertEquals(
			[
				'denyChild' => ['FOO', 'BAR']
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
				'denyChild' => ['FOO']
			],
			iterator_to_array($ruleset)
		);
	}

	/**
	* @testdox allowChild() throws an exception on invalid tag name
	*/
	public function testAllowChildInvalidTagName()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid tag name 'foo#bar'");

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
				'allowChild' => ['B']
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
	*/
	public function testAllowDescendantInvalidTagName()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid tag name 'foo#bar'");

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
				'allowDescendant' => ['B']
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
	* @doesNotPerformAssertions
	*/
	public function testAutoCloseValid()
	{
		$ruleset = new Ruleset;
		$ruleset->autoClose(true);
	}

	/**
	* @testdox autoClose() throws an exception if its argument is not a boolean
	*/
	public function testAutoCloseInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('autoClose() expects a boolean');

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
	* @doesNotPerformAssertions
	*/
	public function testAutoReopenValid()
	{
		$ruleset = new Ruleset;
		$ruleset->autoReopen(true);
	}

	/**
	* @testdox autoReopen() throws an exception if its argument is not a boolean
	*/
	public function testAutoReopenInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('autoReopen() expects a boolean');

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
	* @doesNotPerformAssertions
	*/
	public function testBreakParagraphValid()
	{
		$ruleset = new Ruleset;
		$ruleset->breakParagraph(true);
	}

	/**
	* @testdox breakParagraph() throws an exception if its argument is not a boolean
	*/
	public function testBreakParagraphInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('breakParagraph() expects a boolean');

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
	*/
	public function testCloseAncestorInvalidTagName()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid tag name 'foo#bar'");

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
				'closeAncestor' => ['B']
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
				'closeParent' => ['B']
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
	*/
	public function testCreateChildInvalidTagName()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid tag name 'foo#bar'");

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
				'createChild' => ['B']
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
	* @doesNotPerformAssertions
	*/
	public function testCreateParagraphsValid()
	{
		$ruleset = new Ruleset;
		$ruleset->createParagraphs(true);
	}

	/**
	* @testdox createParagraphs() throws an exception if its argument is not a boolean
	*/
	public function testCreateParagraphsInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('createParagraphs() expects a boolean');

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
	* @testdox ignoreTags() accepts a boolean
	* @doesNotPerformAssertions
	*/
	public function testIgnoreTagsValid()
	{
		$ruleset = new Ruleset;
		$ruleset->ignoreTags(true);
	}

	/**
	* @testdox ignoreTags() throws an exception if its argument is not a boolean
	*/
	public function testIgnoreTagsInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('ignoreTags() expects a boolean');

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
	*/
	public function testDenyChildInvalidTagName()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid tag name 'foo#bar'");

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
				'denyChild' => ['B']
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
	*/
	public function testDenyDescendantInvalidTagName()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid tag name 'foo#bar'");

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
				'denyDescendant' => ['B']
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
	* @doesNotPerformAssertions
	*/
	public function testDisableAutoLineBreaksValid()
	{
		$ruleset = new Ruleset;
		$ruleset->disableAutoLineBreaks(true);
	}

	/**
	* @testdox disableAutoLineBreaks() throws an exception if its argument is not a boolean
	*/
	public function testDisableAutoLineBreaksInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('disableAutoLineBreaks() expects a boolean');

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
	* @doesNotPerformAssertions
	*/
	public function testEnableAutoLineBreaksValid()
	{
		$ruleset = new Ruleset;
		$ruleset->enableAutoLineBreaks(true);
	}

	/**
	* @testdox enableAutoLineBreaks() throws an exception if its argument is not a boolean
	*/
	public function testEnableAutoLineBreaksInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('enableAutoLineBreaks() expects a boolean');

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
	*/
	public function testFosterParentInvalidTagName()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid tag name 'foo#bar'");

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
				'fosterParent' => ['B']
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
	* @doesNotPerformAssertions
	*/
	public function testIgnoreTextValid()
	{
		$ruleset = new Ruleset;
		$ruleset->ignoreText(true);
	}

	/**
	* @testdox ignoreText() throws an exception if its argument is not a boolean
	*/
	public function testIgnoreTextInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('ignoreText() expects a boolean');

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
	* @doesNotPerformAssertions
	*/
	public function testIsTransparentValid()
	{
		$ruleset = new Ruleset;
		$ruleset->isTransparent(true);
	}

	/**
	* @testdox isTransparent() throws an exception if its argument is not a boolean
	*/
	public function testIsTransparentInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('isTransparent() expects a boolean');

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
	* @doesNotPerformAssertions
	*/
	public function testIgnoreSurroundingWhitespaceValid()
	{
		$ruleset = new Ruleset;
		$ruleset->ignoreSurroundingWhitespace(true);
	}

	/**
	* @testdox ignoreSurroundingWhitespace() throws an exception if its argument is not a boolean
	*/
	public function testIgnoreSurroundingWhitespaceInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('ignoreSurroundingWhitespace() expects a boolean');

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
	* @doesNotPerformAssertions
	*/
	public function testPreventLineBreaksValid()
	{
		$ruleset = new Ruleset;
		$ruleset->preventLineBreaks(true);
	}

	/**
	* @testdox preventLineBreaks() throws an exception if its argument is not a boolean
	*/
	public function testPreventLineBreaksInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('preventLineBreaks() expects a boolean');

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
	*/
	public function testRequireAncestorInvalidTagName()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid tag name 'foo#bar'");

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
				'requireAncestor' => ['B']
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
	*/
	public function testRequireParentInvalidTagName()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage("Invalid tag name 'foo#bar'");

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
				'requireParent' => ['B']
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
	* @doesNotPerformAssertions
	*/
	public function testSuspendAutoLineBreaksValid()
	{
		$ruleset = new Ruleset;
		$ruleset->suspendAutoLineBreaks(true);
	}

	/**
	* @testdox suspendAutoLineBreaks() throws an exception if its argument is not a boolean
	*/
	public function testSuspendAutoLineBreaksInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('suspendAutoLineBreaks() expects a boolean');

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
	* @doesNotPerformAssertions
	*/
	public function testTrimWhitespaceValid()
	{
		$ruleset = new Ruleset;
		$ruleset->trimFirstLine(true);
	}

	/**
	* @testdox trimFirstLine() throws an exception if its argument is not a boolean
	*/
	public function testTrimWhitespaceInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('trimFirstLine() expects a boolean');

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
			'allowChild' => ['B'],
			'denyChild'  => ['I']
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
	*/
	public function testSetRulesInvalid()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('merge() expects an array or an instance of Ruleset');

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
			'allowChild'      => 'X',
			'allowDescendant' => 'X',
			'ignoreTags'      => true,
			'denyChild'       => 'X',
			'denyDescendant'  => 'X',
			'isTransparent'   => false,
			'requireParent'   => 'X'
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

		$config = ConfigHelper::filterConfig($ruleset->asConfig(), 'PHP');

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

		$config = ConfigHelper::filterConfig($ruleset->asConfig(), 'PHP');

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