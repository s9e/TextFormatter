<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Parser\TagStack;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\TagStack
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
			array(new Tag(Tag::START_TAG, 'FOO', 12, 34)),
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
			array(new Tag(Tag::END_TAG, 'FOO', 12, 34)),
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
			array(new Tag(Tag::SELF_CLOSING_TAG, 'FOO', 12, 34)),
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
			array($tag),
			$dummyStack->tagStack
		);
	}

	/**
	* @testdox addStartTag() does not add anything to the stack if the tag name does not exist but it returns an invalidated tag
	*/
	public function testAddStartTagInexistent()
	{
		$dummyStack = new DummyStack;
		$tag = $dummyStack->addStartTag('BAR', 12, 34);

		$expected = new Tag(Tag::START_TAG, 'BAR', 12, 34);
		$expected->invalidate();

		$this->assertSame(
			array(),
			$dummyStack->tagStack
		);

		$this->assertEquals(
			$expected,
			$tag
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
			array(),
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
			array(),
			$dummyStack->tagStack
		);

		$this->assertEquals(
			$expected,
			$tag
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
	* @testdox addIgnoreTag() adds self-closing "i" tag to the stack then returns it
	*/
	public function testAddIgnoreTag()
	{
		$dummyStack = new DummyStack;
		$tag = $dummyStack->addIgnoreTag(12, 34);

		$this->assertSame(
			array($tag),
			$dummyStack->tagStack
		);

		$this->assertEquals(
			new Tag(Tag::SELF_CLOSING_TAG, 'i', 12, 34),
			$tag
		);
	}
}

class DummyStack extends Parser
{
	public $tagsConfig = array('FOO' => array());
	public $tagStack = array();
	public function __construct() {}
}