<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\Tag
*/
class TagTest extends Test
{
	/**
	* @testdox getAttributes() returns the tag's attributes as an array
	*/
	public function testGetAttributes()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34);
		$this->assertSame([], $tag->getAttributes());
	}

	/**
	* @testdox getEndTag() returns NULL if the tag has no end tag set
	*/
	public function testGetEndTag()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34);
		$this->assertNull($tag->getEndTag());
	}

	/**
	* @testdox getPos() returns the tag's length (amount of text consumed)
	*/
	public function testGetLen()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34);
		$this->assertSame(34, $tag->getLen());
	}

	/**
	* @testdox getName() returns the tag's name
	*/
	public function testGetName()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34);
		$this->assertSame('X', $tag->getName());
	}

	/**
	* @testdox getPos() returns the tag's position
	*/
	public function testGetPos()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34);
		$this->assertSame(12, $tag->getPos());
	}

	/**
	* @testdox getSortPriority() returns the tag's sortPriority
	*/
	public function testGetSortPriority()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34);
		$this->assertSame(0, $tag->getSortPriority());
	}

	/**
	* @testdox getStartTag() returns NULL if the tag has no start tag set
	*/
	public function testGetStartTag()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34);
		$this->assertNull($tag->getStartTag());
	}

	/**
	* @testdox getType() returns the tag's type
	*/
	public function testGetType()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 12, 34);
		$this->assertSame(Tag::SELF_CLOSING_TAG, $tag->getType());
	}

	/**
	* @testdox isInvalid() returns false by default
	*/
	public function testIsInvalidFalse()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 12, 34);
		$this->assertFalse($tag->isInvalid());
	}

	/**
	* @testdox isBrTag() returns true if the tag's name is "br"
	*/
	public function testIsBrTagTrue()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'br', 12, 0);
		$this->assertTrue($tag->isBrTag());
	}

	/**
	* @testdox isBrTag() returns false by default
	*/
	public function testIsBrTagFalse()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'BR', 12, 0);
		$this->assertFalse($tag->isBrTag());
	}

	/**
	* @testdox isIgnoreTag() returns true if the tag's name is "i"
	*/
	public function testIsIgnoreTagTrue()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'i', 12, 34);
		$this->assertTrue($tag->isIgnoreTag());
	}

	/**
	* @testdox isIgnoreTag() returns false by default
	*/
	public function testIsIgnoreTagFalse()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'I', 12, 0);
		$this->assertFalse($tag->isIgnoreTag());
	}

	/**
	* @testdox isParagraphBreak() returns true if the tag's name is "pb"
	*/
	public function testIsParagraphBreakTrue()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'pb', 12, 0);
		$this->assertTrue($tag->isParagraphBreak());
	}

	/**
	* @testdox isParagraphBreak() returns false by default
	*/
	public function testIsParagraphBreakFalse()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'BR', 12, 0);
		$this->assertFalse($tag->isParagraphBreak());
	}

	/**
	* @testdox isStartTag() returns true if the tag's type is Tag::START_TAG
	*/
	public function testIsStartTagStart()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34);
		$this->assertTrue($tag->isStartTag());
	}

	/**
	* @testdox isStartTag() returns true if the tag's type is Tag::END_TAG
	*/
	public function testIsStartTagEnd()
	{
		$tag = new Tag(Tag::END_TAG, 'X', 12, 34);
		$this->assertFalse($tag->isStartTag());
	}

	/**
	* @testdox isStartTag() returns true if the tag's type is Tag::SELF_CLOSING_TAG
	*/
	public function testIsStartTagSelfClosing()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 12, 34);
		$this->assertTrue($tag->isStartTag());
	}

	/**
	* @testdox isEndTag() returns false if the tag's type is Tag::START_TAG
	*/
	public function testIsEndTagStart()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34);
		$this->assertFalse($tag->isEndTag());
	}

	/**
	* @testdox isEndTag() returns true if the tag's type is Tag::END_TAG
	*/
	public function testIsEndTagEnd()
	{
		$tag = new Tag(Tag::END_TAG, 'X', 12, 34);
		$this->assertTrue($tag->isEndTag());
	}

	/**
	* @testdox isEndTag() returns true if the tag's type is Tag::SELF_CLOSING_TAG
	*/
	public function testIsEndTagSelfClosing()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 12, 34);
		$this->assertTrue($tag->isEndTag());
	}

	/**
	* @testdox isSelfClosingTag() returns false if the tag's type is Tag::START_TAG
	*/
	public function testIsSelfClosingTagStart()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34);
		$this->assertFalse($tag->isSelfClosingTag());
	}

	/**
	* @testdox isSelfClosingTag() returns false if the tag's type is Tag::END_TAG
	*/
	public function testIsSelfClosingTagEnd()
	{
		$tag = new Tag(Tag::END_TAG, 'X', 12, 34);
		$this->assertFalse($tag->isSelfClosingTag());
	}

	/**
	* @testdox isSelfClosingTag() returns true if the tag's type is Tag::SELF_CLOSING_TAG
	*/
	public function testIsSelfClosingTagSelfClosing()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 12, 34);
		$this->assertTrue($tag->isSelfClosingTag());
	}

	/**
	* @testdox isSystemTag() returns false by default
	*/
	public function testIsSystemTagDefault()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 12, 34);
		$this->assertFalse($tag->isSystemTag());
	}

	/**
	* @testdox isSystemTag() returns true if the tag's name is "i"
	*/
	public function testIsSystemTagIgnore()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'i', 10, 10);
		$this->assertTrue($tag->isSystemTag());
	}

	/**
	* @testdox isSystemTag() returns true if the tag's name is "br"
	*/
	public function testIsSystemTagLineBreak()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'pb', 0, 0);
		$this->assertTrue($tag->isSystemTag());
	}

	/**
	* @testdox isSystemTag() returns true if the tag's name is "pb"
	*/
	public function testIsSystemTagParagraphBreak()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'pb', 0, 0);
		$this->assertTrue($tag->isSystemTag());
	}

	/**
	* @testdox isVerbatim() returns true if the tag's name is "v"
	*/
	public function testIsVerbatimTrue()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'v', 12, 0);
		$this->assertTrue($tag->isVerbatim());
	}

	/**
	* @testdox isVerbatim() returns false by default
	*/
	public function testIsVerbatimFalse()
	{
		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'BR', 12, 0);
		$this->assertFalse($tag->isVerbatim());
	}

	/**
	* @testdox invalidate() makes isInvalid() return true
	*/
	public function testInvalidate()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->invalidate();

		$this->assertTrue($tag->isInvalid());
	}

	/**
	* @testdox $tag1->cascadeInvalidationTo($tag2) causes $tag1->invalidate() to call $tag2->invalidate()
	*/
	public function testInvalidateCascade()
	{
		$tag1 = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag2 = $this->getMockBuilder('s9e\\TextFormatter\\Parser\\Tag')
		             ->onlyMethods(['invalidate'])
		             ->setConstructorArgs([Tag::START_TAG, 'X', 0, 0])
		             ->getMock();
		$tag2->expects($this->once())
		     ->method('invalidate');

		$tag1->cascadeInvalidationTo($tag2);
		$tag1->invalidate();
	}

	/**
	* @testdox $tag1->cascadeInvalidationTo($tag2) does not cause $tag2->invalidate() to call $tag1->invalidate()
	*/
	public function testInvalidateCascadeNotReciprocal()
	{
		$tag1 = $this->getMockBuilder('s9e\\TextFormatter\\Parser\\Tag')
		             ->onlyMethods(['invalidate'])
		             ->setConstructorArgs([Tag::START_TAG, 'X', 0, 0])
		             ->getMock();
		$tag1->expects($this->never())
		     ->method('invalidate');
		$tag2 = new Tag(Tag::START_TAG, 'X', 0, 0);

		$tag1->cascadeInvalidationTo($tag2);
		$tag2->invalidate();
	}

	/**
	* @testdox $tag1->cascadeInvalidationTo($tag2) immediately calls $tag2->invalidate() if $tag1 is invalid
	*/
	public function testInvalidateCascadeImmediate()
	{
		$tag1 = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag2 = $this->getMockBuilder('s9e\\TextFormatter\\Parser\\Tag')
		             ->onlyMethods(['invalidate'])
		             ->setConstructorArgs([Tag::START_TAG, 'X', 0, 0])
		             ->getMock();
		$tag2->expects($this->once())
		     ->method('invalidate');

		$tag1->invalidate();
		$tag1->cascadeInvalidationTo($tag2);
	}

	/**
	* @testdox Mutual invalidation doesn't cause an infinite loop
	* @doesNotPerformAssertions
	*/
	public function testInvalidateNoInfiniteLoop()
	{
		$tag1 = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag2 = new Tag(Tag::START_TAG, 'X', 0, 0);

		$tag1->cascadeInvalidationTo($tag2);
		$tag2->cascadeInvalidationTo($tag1);

		$tag1->invalidate();
	}

	/**
	* @testdox Invalidating a start tag automatically cascades to its paired tag
	*/
	public function testInvalidateCascadesOnPaired()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG, 'X', 1, 0);
		$startTag->pairWith($endTag);
		$startTag->invalidate();
		$this->assertTrue($endTag->isInvalid());
	}

	/**
	* @testdox $tag1->pairWith($tag2) does not do anything if the tags have different names
	*/
	public function testPairWithDifferentNames()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'Y', 0, 0);

		$st = serialize($startTag);
		$et = serialize($endTag);

		$startTag->pairWith($endTag);

		$this->assertSame($st, serialize($startTag), '$startTag has been modified');
		$this->assertSame($et, serialize($endTag),   '$endTag has been modified');
	}

	/**
	* @testdox $startTag->pairWith($endTag) does not do anything if $startTag's position is greater than $endTag's
	*/
	public function testPairWithStartAfterEnd()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 1, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 0, 0);

		$st = serialize($startTag);
		$et = serialize($endTag);

		$startTag->pairWith($endTag);

		$this->assertSame($st, serialize($startTag), '$startTag has been modified');
		$this->assertSame($et, serialize($endTag),   '$endTag has been modified');
	}

	/**
	* @testdox $startTag->pairWith($endTag) sets $endTag->startTag if they share the same position
	*/
	public function testPairWithGetStartTagSamePos()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 0, 0);

		$startTag->pairWith($endTag);

		$this->assertSame($startTag, $endTag->getStartTag());
	}

	/**
	* @testdox $startTag->pairWith($endTag) sets $endTag->startTag if $endTag's position is greater than $startTag's
	*/
	public function testPairWithGetStartTag()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 1, 0);

		$startTag->pairWith($endTag);

		$this->assertSame($startTag, $endTag->getStartTag());
	}

	/**
	* @testdox $startTag->pairWith($endTag) sets $startTag->endTag if they share the same position
	*/
	public function testPairWithGetEndTagSamePos()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 0, 0);

		$startTag->pairWith($endTag);

		$this->assertSame($endTag, $startTag->getEndTag());
	}

	/**
	* @testdox $startTag->pairWith($endTag) sets $startTag->endTag if $endTag's position is greater than $startTag's
	*/
	public function testPairWithGetEndTag()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 1, 0);

		$startTag->pairWith($endTag);

		$this->assertSame($endTag, $startTag->getEndTag());
	}

	/**
	* @testdox $endTag->pairWith($startTag) sets $endTag->startTag if they share the same position
	*/
	public function testReversePairWithGetStartTagSamePos()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 0, 0);

		$endTag->pairWith($startTag);

		$this->assertSame($startTag, $endTag->getStartTag());
	}

	/**
	* @testdox $endTag->pairWith($startTag) sets $endTag->startTag if $endTag's position is greater than $startTag's
	*/
	public function testReversePairWithGetStartTag()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 1, 0);

		$endTag->pairWith($startTag);

		$this->assertSame($startTag, $endTag->getStartTag());
	}

	/**
	* @testdox $endTag->pairWith($startTag) sets $startTag->endTag if they share the same position
	*/
	public function testReversePairWithGetEndTagSamePos()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 0, 0);

		$endTag->pairWith($startTag);

		$this->assertSame($endTag, $startTag->getEndTag());
	}

	/**
	* @testdox $endTag->pairWith($startTag) sets $startTag->endTag if $endTag's position is greater than $startTag's
	*/
	public function testReversePairWithGetEndTag()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 1, 0);

		$endTag->pairWith($startTag);

		$this->assertSame($endTag, $startTag->getEndTag());
	}

	/**
	* @testdox Tag's sortPriority can be set in the constructor
	*/
	public function testConstructorSortPriority()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 12, 34, -10);
		$this->assertSame(-10, $tag->getSortPriority());
	}

	/**
	* @testdox setAttribute('foo', 'bar') sets attribute 'foo' to 'bar'
	*/
	public function testSetAttribute()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'bar');

		$this->assertSame(['foo' => 'bar'], $tag->getAttributes());
	}

	/**
	* @testdox setAttribute() overwrites existing attributes
	*/
	public function testSetAttributeOverwrite()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'bar');
		$tag->setAttribute('foo', 'xxx');

		$this->assertSame(['foo' => 'xxx'], $tag->getAttributes());
	}

	/**
	* @testdox setAttributes() sets multiple attributes at once
	*/
	public function testSetAttributes()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setAttributes(['foo' => 'bar', 'baz' => 'quux']);

		$this->assertSame(['foo' => 'bar', 'baz' => 'quux'], $tag->getAttributes());
	}

	/**
	* @testdox setAttributes() removes all other attributes
	*/
	public function testSetAttributesNuke()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setAttribute('x', 'x');
		$tag->setAttributes(['foo' => 'bar', 'baz' => 'quux']);

		$this->assertSame(['foo' => 'bar', 'baz' => 'quux'], $tag->getAttributes());
	}

	/**
	* @testdox getAttribute('foo') returns the value of attribute 'foo'
	*/
	public function testGetAttribute()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'bar');

		$this->assertSame('bar', $tag->getAttribute('foo'));
	}

	/**
	* @testdox hasAttribute('foo') returns false if attribute 'foo' is not set
	*/
	public function testHasAttributeNegative()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);

		$this->assertFalse($tag->hasAttribute('foo'));
	}

	/**
	* @testdox hasAttribute('foo') returns true if attribute 'foo' is set
	*/
	public function testHasAttributePositive()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'bar');

		$this->assertTrue($tag->hasAttribute('foo'));
	}

	/**
	* @testdox removeAttribute('foo') unsets attribute 'foo'
	*/
	public function testRemoveAttribute()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'bar');
		$tag->setAttribute('baz', 'quux');
		$tag->removeAttribute('foo');

		$this->assertSame(['baz' => 'quux'], $tag->getAttributes());
	}

	/**
	* @testdox canClose() returns FALSE if this tag is invalid
	*/
	public function testCanCloseInvalid()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 1, 0);
		$endTag->invalidate();

		$this->assertFalse($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns FALSE if the tag names don't match
	*/
	public function testCanCloseDifferentNames()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'Y', 1, 0);

		$this->assertFalse($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns FALSE if given tag is a self-closing tag
	*/
	public function testCanCloseTargetSelfClosing()
	{
		$startTag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,          'X', 1, 0);

		$this->assertFalse($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns FALSE if given tag is an end tag
	*/
	public function testCanCloseTargetEnd()
	{
		$startTag = new Tag(Tag::END_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG, 'X', 1, 0);

		$this->assertFalse($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns FALSE if this tag is a self-closing tag
	*/
	public function testCanCloseSelfClosing()
	{
		$startTag = new Tag(Tag::START_TAG,        'X', 0, 0);
		$endTag   = new Tag(Tag::SELF_CLOSING_TAG, 'X', 1, 0);

		$this->assertFalse($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns FALSE if this tag is a start tag
	*/
	public function testCanCloseStart()
	{
		$startTag = new Tag(Tag::END_TAG,   'X', 0, 0);
		$endTag   = new Tag(Tag::START_TAG, 'X', 1, 0);

		$this->assertFalse($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns FALSE if this tag's position is before given tag's
	*/
	public function testCanCloseBeforePos()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 1, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 0, 0);

		$this->assertFalse($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns TRUE if this tag's position is the same as given tag's
	*/
	public function testCanCloseSamePos()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 0, 0);

		$this->assertTrue($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns TRUE if this tag's position is after given tag's
	*/
	public function testCanCloseAfterPos()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 1, 0);

		$this->assertTrue($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns FALSE if given tag is paired with another tag
	*/
	public function testCanCloseStartPairInvalid()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 1, 0);

		$startTag->pairWith(clone $endTag);

		$this->assertFalse($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns FALSE if this tag is paired with another tag
	*/
	public function testCanCloseEndPairInvalid()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 1, 0);

		$endTag->pairWith(clone $startTag);

		$this->assertFalse($endTag->canClose($startTag));
	}

	/**
	* @testdox canClose() returns TRUE if the tags are paired together
	*/
	public function testCanClosePaired()
	{
		$startTag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$endTag   = new Tag(Tag::END_TAG,   'X', 1, 0);

		$endTag->pairWith($startTag);

		$this->assertTrue($endTag->canClose($startTag));
	}

	/**
	* @testdox setFlags() sets the tag's flags
	*/
	public function testSetFlags()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setFlags(4);

		$this->assertSame(4, $this->getObjectProperty($tag, 'flags'));
	}

	/**
	* @testdox getFlags() returns the tag's flags
	*/
	public function testGetFlags()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setFlags(32);

		$this->assertSame(32, $tag->getFlags());
	}

	/**
	* @testdox addFlags() adds given flags to the tag's
	*/
	public function testAddFlags()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setFlags(2);
		$tag->addFlags(5);

		$this->assertSame(7, $tag->getFlags());
	}

	/**
	* @testdox removeFlags() removes given flags from the tag's
	*/
	public function testRemoveFlags()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setFlags(7);
		$tag->removeFlags(5);

		$this->assertSame(2, $tag->getFlags());
	}

	/**
	* @testdox removeFlags() can be called on flags that haven't been set
	*/
	public function testRemoveFlagsUnset()
	{
		$tag = new Tag(Tag::START_TAG, 'X', 0, 0);
		$tag->setFlags(2);
		$tag->removeFlags(5);

		$this->assertSame(2, $tag->getFlags());
	}
}