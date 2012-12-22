<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Configurator\Items\Tag as TagConfig;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\FilterProcessing;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\FilterProcessing
*/
class FilterProcessingTest extends Test
{
	/**
	* @testdox registerVar($k, $v) sets registeredVars[$k] to $v
	*/
	public function testRegisterVar()
	{
		$dummy = new FilterProcessingDummy;
		$dummy->registerVar('foo', 'bar');

		$this->assertSame(
			array('foo' => 'bar'),
			$dummy->registeredVars
		);
	}

	/**
	* @testdox executeAttributePreprocessors() sets captured attributes on match
	*/
	public function testExecuteAttributePreprocessorsSetAttributesOnMatch()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');

		$this->assertTrue(FilterProcessingDummy::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertSame('2', $tag->getAttribute('bar'));
		$this->assertSame('x', $tag->getAttribute('baz'));
	}

	/**
	* @testdox executeAttributePreprocessors() does not overwrite attributes that were already set
	*/
	public function testExecuteAttributePreprocessorsDoesNotOverwrite()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');
		$tag->setAttribute('bar', '4');

		$this->assertTrue(FilterProcessingDummy::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertSame('4', $tag->getAttribute('bar'));
		$this->assertSame('x', $tag->getAttribute('baz'));
	}

	/**
	* @testdox executeAttributePreprocessors() unsets the source attribute on match
	*/
	public function testExecuteAttributePreprocessorsUnsetsSource()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');

		$this->assertTrue(FilterProcessingDummy::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertFalse($tag->hasAttribute('foo'));
	}

	/**
	* @testdox executeAttributePreprocessors() does not unset the source attribute if there's nn match
	*/
	public function testExecuteAttributePreprocessorsDoesNotUnsetSourceIfNoMatch()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', 'xx');

		$this->assertTrue(FilterProcessingDummy::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertTrue($tag->hasAttribute('foo'));
	}

	/**
	* @testdox executeAttributePreprocessors() stops after the first match
	*/
	public function testExecuteAttributePreprocessorsStopsOnFirstMatch()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])/i');
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');

		$this->assertTrue(FilterProcessingDummy::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertSame('2', $tag->getAttribute('bar'));
		$this->assertFalse($tag->hasAttribute('baz'));
	}

	/**
	* @testdox executeAttributePreprocessors() tries all preprocessors until there's a match
	*/
	public function testExecuteAttributePreprocessorsTriesAll()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[a-z])(?<baz>[a-z])$/i');
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[0-9])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);
		$tag->setAttribute('foo', '2x');

		$this->assertTrue(FilterProcessingDummy::executeAttributePreprocessors($tag, $tagConfig));
		$this->assertSame('2', $tag->getAttribute('bar'));
		$this->assertSame('x', $tag->getAttribute('baz'));
	}

	/**
	* @testdox executeAttributePreprocessors() returns TRUE even if the no source attribute was present
	*/
	public function testExecuteAttributePreprocessorsReturnsTrue()
	{
		$tagConfig = new TagConfig;
		$tagConfig->attributePreprocessors->add('foo', '/^(?<bar>[a-z])(?<baz>[a-z])$/i');
		$tagConfig = $tagConfig->asConfig();

		$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 0, 0);

		$this->assertTrue(FilterProcessingDummy::executeAttributePreprocessors($tag, $tagConfig));
	}
}

class FilterProcessingDummy extends Parser
{
	public $registeredVars;
	public $tagsConfig = array(
	);

	public function __construct()
	{
	}
}