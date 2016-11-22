<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser
*/
class TagStackTest extends Test
{
	/**
	* @testdox addStartTag() adds a start tag to the stack
	*/
	public function testAddStartTag()
	{
		$dummyStack = new DummyStack;
		$dummyStack->addStartTag('FOO', 12, 34);

		$this->assertEquals(
			[new Tag(Tag::START_TAG, 'FOO', 12, 34)],
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox addEndTag() adds an end tag to the stack
	*/
	public function testAddEndTag()
	{
		$dummyStack = new DummyStack;
		$dummyStack->addEndTag('FOO', 12, 34);

		$this->assertEquals(
			[new Tag(Tag::END_TAG, 'FOO', 12, 34)],
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox addSelfClosingTag() adds a self-closing tag to the stack
	*/
	public function testAddSelfClosingTag()
	{
		$dummyStack = new DummyStack;
		$dummyStack->addSelfClosingTag('FOO', 12, 34);

		$this->assertEquals(
			[new Tag(Tag::SELF_CLOSING_TAG, 'FOO', 12, 34)],
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox addStartTag() returns the newly added tag
	*/
	public function testAddStartTagReturn()
	{
		$dummyStack = new DummyStack;
		$tag = $dummyStack->addStartTag('FOO', 12, 34);

		$this->assertSame(
			[$tag],
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox addStartTag() does not add anything to the stack if the tag name does not exist but it returns an invalidated tag
	*/
	public function testAddStartTagDisabled()
	{
		$dummyStack = new DummyStack;
		$tag = $dummyStack->addStartTag('BAR', 12, 34);

		$expected = new Tag(Tag::START_TAG, 'BAR', 12, 34);
		$expected->invalidate();

		$this->assertSame(
			[],
			$dummyStack->tagStack
		);

		$this->assertEquals(
			$expected,
			$tag
		);
	}

	/**
	* @testdox addStartTag() does not add the created tag to the stack if the tag has been disabled
	*/
	public function testAddStartTagInexistent()
	{
		$dummyStack = new DummyStack;
		$tag = $dummyStack->addStartTag('DISABLED', 12, 34);

		$expected = new Tag(Tag::START_TAG, 'DISABLED', 12, 34);
		$expected->invalidate();

		$this->assertSame(
			[],
			$dummyStack->tagStack
		);

		$this->assertEquals(
			$expected,
			$tag
		);

		$this->assertEquals(
			[
				[
					'warn',
					'Tag is disabled',
					[
						'tagName' => 'DISABLED',
						'tag'     => $tag
					]
				]
			],
			$dummyStack->getLogger()->get()
		);
	}

	/**
	* @testdox addStartTag() does not add anything to the stack if the position is negative but it returns an invalidated tag
	*/
	public function testAddStartTagNegativePos()
	{
		$dummyStack = new DummyStack;
		$tag = $dummyStack->addStartTag('FOO', -12, 34);

		$expected = new Tag(Tag::START_TAG, 'FOO', -12, 34);
		$expected->invalidate();

		$this->assertSame(
			[],
			$dummyStack->tagStack
		);

		$this->assertEquals(
			$expected,
			$tag
		);
	}

	/**
	* @testdox addStartTag() does not add anything to the stack if the length is negative but it returns an invalidated tag
	*/
	public function testAddStartTagLengthPos()
	{
		$dummyStack = new DummyStack;
		$tag = $dummyStack->addStartTag('FOO', 12, -34);

		$expected = new Tag(Tag::START_TAG, 'FOO', 12, -34);
		$expected->invalidate();

		$this->assertSame(
			[],
			$dummyStack->tagStack
		);

		$this->assertEquals(
			$expected,
			$tag
		);
	}

	/**
	* @testdox addTagPair() creates both a start tag and an end tag of the same name, paired together
	*/
	public function testAddTagPair()
	{
		$dummyStack = new DummyStack;
		$dummyStack->addTagPair('FOO', 1, 2, 3, 4);

		$startTag = new Tag(Tag::START_TAG, 'FOO', 1, 2);
		$endTag   = new Tag(Tag::END_TAG,   'FOO', 3, 4);
		$startTag->pairWith($endTag);

		$this->assertEquals(
			[$endTag, $startTag],
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox addTagPair() returns the newly-created start tag
	*/
	public function testAddTagPairReturn()
	{
		$dummyStack = new DummyStack;

		$startTag = new Tag(Tag::START_TAG, 'FOO', 1, 2);
		$endTag   = new Tag(Tag::END_TAG,   'FOO', 3, 4);
		$startTag->pairWith($endTag);

		$this->assertEquals(
			$startTag,
			$dummyStack->addTagPair('FOO', 1, 2, 3, 4)
		);
	}

	/**
	* @testdox addBrTag() adds a zero-width, self-closing "br" tag to the stack then returns it
	*/
	public function testAddBrTag()
	{
		$dummyStack = new DummyStack;
		$tag = $dummyStack->addBrTag(12);

		$this->assertEquals(
			new Tag(Tag::SELF_CLOSING_TAG, 'br', 12, 0),
			$tag
		);
	}

	/**
	* @testdox addIgnoreTag() adds a self-closing "i" tag to the stack then returns it
	*/
	public function testAddIgnoreTag()
	{
		$dummyStack = new DummyStack;
		$tag = $dummyStack->addIgnoreTag(12, 34);

		$this->assertSame(
			[$tag],
			$dummyStack->tagStack
		);

		$this->assertEquals(
			new Tag(Tag::SELF_CLOSING_TAG, 'i', 12, 34),
			$tag
		);
	}

	/**
	* @testdox addParagraphBreak() adds a zero-width, self-closing "pb" tag to the stack then returns it
	*/
	public function testAddParagraphBreak()
	{
		$dummyStack = new DummyStack;
		$tag = $dummyStack->addParagraphBreak(12);

		$this->assertEquals(
			new Tag(Tag::SELF_CLOSING_TAG, 'pb', 12, 0),
			$tag
		);
	}

	/**
	* @testdox addCopyTag() adds a copy of given tag at given position and given length
	*/
	public function testAddCopyTag()
	{
		$dummyStack = new DummyStack;
		$original = $dummyStack->addSelfClosingTag('X', 1, 2);
		$copy     = $dummyStack->addCopyTag($original, 11, 22);
		$expected = $dummyStack->addSelfClosingTag('X', 11, 22);

		$this->assertEquals($expected, $copy);
	}

	/**
	* @testdox addCopyTag() copies the original tag's attributes
	*/
	public function testAddCopyTagAttributes()
	{
		$dummyStack = new DummyStack;
		$original = $dummyStack->addSelfClosingTag('X', 1, 2);
		$original->setAttribute('foo', 'bar');
		$copy     = $dummyStack->addCopyTag($original, 11, 22);

		$this->assertSame('bar', $copy->getAttribute('foo'));
	}

	/**
	* @testdox addCopyTag() copies the original tag's sort priority
	*/
	public function testAddCopyTagSortPriority()
	{
		$dummyStack = new DummyStack;
		$original = $dummyStack->addSelfClosingTag('X', 1, 2, 123);
		$copy     = $dummyStack->addCopyTag($original, 11, 22);

		$this->assertSame(123, $copy->getSortPriority());
	}

	/**
	* @testdox addCopyTag() can set a different sort priority
	*/
	public function testAddCopyTagNewSortPriority()
	{
		$dummyStack = new DummyStack;
		$original = $dummyStack->addSelfClosingTag('X', 1, 2, 123);
		$copy     = $dummyStack->addCopyTag($original, 11, 22, 456);

		$this->assertSame(456, $copy->getSortPriority());
	}

	/**
	* @testdox sortTags() sorts tags by position descending (tags processed in text order)
	*/
	public function testSortTagsByPos()
	{
		$dummyStack = new DummyStack;

		$y = $dummyStack->addStartTag('Y', 1, 1);
		$x = $dummyStack->addStartTag('X', 0, 1);
		$z = $dummyStack->addStartTag('Z', 2, 1);

		$dummyStack->sortTags();

		$this->assertSame(
			[$z, $y, $x],
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox sortTags() tiebreaker sorts tag that start at the same position by sortPriority descending (-10 is processed before 10)
	*/
	public function testSortTagsBySortPriority()
	{
		$dummyStack = new DummyStack;

		$t2 = $dummyStack->addStartTag('X', 0, 2, 0);
		$t3 = $dummyStack->addStartTag('X', 0, 3, 10);
		$t1 = $dummyStack->addStartTag('X', 0, 1, -10);

		$dummyStack->sortTags();

		$this->assertSame(
			[$t3, $t2, $t1],
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox sortTags() tiebreaker sorts zero-width tags after longer tags at the same position with the same priority (zero-width tags are processed first)
	*/
	public function testSortTagsZeroWidthAfterWider()
	{
		$dummyStack = new DummyStack;

		$t0 = $dummyStack->addStartTag('X', 0, 0);
		$t1 = $dummyStack->addStartTag('X', 0, 1);
		$t3 = $dummyStack->addStartTag('X', 1, 1);
		$t2 = $dummyStack->addStartTag('X', 1, 0);

		$dummyStack->sortTags();

		$this->assertSame(
			[$t3, $t2, $t1, $t0],
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox sortTags() tiebreaker sorts zero-width end tags after zero width start tag (end tags get the opportunity to close their parent before a new tag is open)
	*/
	public function testSortTagsZeroWidthEndAfterZeroWidthStart()
	{
		$dummyStack = new DummyStack;

		$expected = [
			$dummyStack->addStartTag('X', 1, 0),
			$dummyStack->addEndTag('X', 1, 0),
			$dummyStack->addStartTag('X', 0, 0),
			$dummyStack->addEndTag('X', 0, 0)
		];

		$dummyStack->sortTags();

		$this->assertSame(
			$expected,
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox sortTags() tiebreaker sorts zero-width self-closing tags between zero-width start tags and zero-width end tags (attempting to keep them outside of tag pairs)
	*/
	public function testSortTagsZeroWidthSelfClosingBetweenStartAndEnd()
	{
		$dummyStack = new DummyStack;

		$t2 = $dummyStack->addStartTag('X', 1, 0);
		$t1 = $dummyStack->addSelfClosingTag('X', 1, 0);
		$t0 = $dummyStack->addEndTag('X', 1, 0);

		$dummyStack->sortTags();

		$this->assertSame(
			[$t2, $t1, $t0],
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox sortTags() tiebreaker sorts tags by length ascending (longer tags processed first)
	*/
	public function testSortTagsByLen()
	{
		$dummyStack = new DummyStack;

		$t3 = $dummyStack->addStartTag('X', 0, 3);
		$t1 = $dummyStack->addStartTag('X', 0, 1);
		$t2 = $dummyStack->addStartTag('X', 0, 2);

		$dummyStack->sortTags();

		$this->assertSame(
			[$t1, $t2, $t3],
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox Tags occupying the same position are properly sorted regardless of the order they've been added
	*/
	public function testTagsAtSamePosAreSorted()
	{
		$this->configurator->tags->add('X');
		$this->configurator->tags->add('Y');

		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$parser->addSelfClosingTag('Y', 0, 0, 20);
				$parser->addSelfClosingTag('X', 0, 0, 10);
			}
		);
		$this->assertSame(
			'<r><X/><Y/></r>',
			$parser->parse('')
		);

		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$parser->addSelfClosingTag('X', 0, 0, 10);
				$parser->addSelfClosingTag('Y', 0, 0, 20);
			}
		);
		$this->assertSame(
			'<r><X/><Y/></r>',
			$parser->parse('')
		);
	}

	/**
	* @testdox addBrTag() sets the tag's priority
	*/
	public function testAddBrTagPrio()
	{
		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$this->assertSame(-1, $parser->addBrTag(0, -1)->getSortPriority());
			}
		);
		$parser->parse('');
	}

	/**
	* @testdox addEndTag() sets the tag's priority
	*/
	public function testAddEndTagPrio()
	{
		$this->configurator->tags->add('X');
		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$this->assertSame(-1, $parser->addEndTag('X', 0, 0, -1)->getSortPriority());
			}
		);
		$parser->parse('');
	}

	/**
	* @testdox addIgnoreTag() sets the tag's priority
	*/
	public function testAddIgnoreTagPrio()
	{
		$this->configurator->tags->add('X');
		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$this->assertSame(-1, $parser->addIgnoreTag(0, 1, -1)->getSortPriority());
			}
		);
		$parser->parse(' ');
	}

	/**
	* @testdox addParagraphBreak() sets the tag's priority
	*/
	public function testAddParagraphBreakPrio()
	{
		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$this->assertSame(9, $parser->addParagraphBreak(0, 9)->getSortPriority());
			}
		);
		$parser->parse('');
	}

	/**
	* @testdox addSelfClosingTag() sets the tag's priority
	*/
	public function testAddSelfClosingTagPrio()
	{
		$this->configurator->tags->add('X');
		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$this->assertSame(10, $parser->addSelfClosingTag('X', 0, 0, 10)->getSortPriority());
			}
		);
		$parser->parse('');
	}

	/**
	* @testdox addStartTag() sets the tag's priority
	*/
	public function testAddStartTagPrio()
	{
		$this->configurator->tags->add('X');
		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$this->assertSame(-10, $parser->addStartTag('X', 0, 0, -10)->getSortPriority());
			}
		);
		$parser->parse('');
	}

	/**
	* @testdox addTagPair() sets the start tag's priority to given value
	*/
	public function testAddTagPairStartTagPrio()
	{
		$this->configurator->tags->add('X');
		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$startTag = $parser->addTagPair('X', 0, 0, 1, 0, 6);
				$endTag   = $startTag->getEndTag();
				$this->assertSame(6, $startTag->getSortPriority());
			}
		);
		$parser->parse(' ');
	}

	/**
	* @testdox addTagPair() sets the end tag's priority to minus the given value
	*/
	public function testAddTagPairEndTagPrio()
	{
		$this->configurator->tags->add('X');
		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$startTag = $parser->addTagPair('X', 0, 0, 1, 0, 6);
				$endTag   = $startTag->getEndTag();
				$this->assertSame(-6, $endTag->getSortPriority());
			}
		);
		$parser->parse(' ');
	}

	/**
	* @testdox addVerbatim() sets the tag's priority
	*/
	public function testAddVerbatimPrio()
	{
		$parser = $this->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$this->assertSame(-99, $parser->addVerbatim(0, 1, -99)->getSortPriority());
			}
		);
		$parser->parse(' ');
	}
}

class DummyStack extends Parser
{
	public $textLen = 9999;
	public $tagsConfig = [
		'FOO' => ['rules' => ['flags' => 0]],
		'X' => ['rules' => ['flags' => 0]],
		'Y' => ['rules' => ['flags' => 0]],
		'Z' => ['rules' => ['flags' => 0]],
		'DISABLED' => ['isDisabled' => true, 'rules' => ['flags' => 0]]
	];
	public $tagStack = [];
	public function __construct()
	{
		$this->logger = new Logger;
	}
	public function sortTags()
	{
		parent::sortTags();
	}
}