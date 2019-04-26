<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Items\Tag
*/
class TagTest extends Test
{
	/**
	* @testdox An array of options can be passed to the constructor
	*/
	public function testConstructorOptions()
	{
		$tag = new Tag(['nestingLimit' => 123]);
		$this->assertSame(123, $tag->nestingLimit);
	}

	/**
	* @testdox $tag->attributePreprocessors can be assigned a 2D array of regexps
	*/
	public function testAttributePreprocessorsArray()
	{
		$attributePreprocessors = [
			['foo', '/a/'],
			['foo', '/b/'],
			['bar', '/c/']
		];

		$expected = new AttributePreprocessorCollection;
		$expected->add('foo', '/a/');
		$expected->add('foo', '/b/');
		$expected->add('bar', '/c/');

		$tag = new Tag;
		$tag->attributePreprocessors = $attributePreprocessors;

		$this->assertEquals(
			$expected->asConfig(),
			$tag->attributePreprocessors->asConfig()
		);
	}

	/**
	* @testdox $tag->attributePreprocessors can be assigned an instance of AttributePreprocessorCollection to copy its content
	*/
	public function testAttributePreprocessorsInstanceOfAttributePreprocessorCollection()
	{
		$attributePreprocessorCollection = new AttributePreprocessorCollection;
		$attributePreprocessorCollection->add('foo', '/bar/');

		$tag = new Tag;
		$tag->attributePreprocessors = $attributePreprocessorCollection;

		$this->assertEquals(
			$attributePreprocessorCollection,
			$tag->attributePreprocessors
		);

		$this->assertNotSame(
			$attributePreprocessorCollection,
			$tag->attributePreprocessors,
			'$tag->attributePreprocessor should not have been replaced with $attributePreprocessorCollection'
		);
	}

	/**
	* @testdox Setting $tag->attributePreprocessors clears previous attributePreprocessors
	* @depends testAttributePreprocessorsArray
	*/
	public function testAttributePreprocessorsArrayClears()
	{
		$attributePreprocessors = [
			['foo', '/a/'],
			['foo', '/b/'],
			['bar', '/c/']
		];

		$expected = new AttributePreprocessorCollection;
		$expected->add('foo', '/a/');
		$expected->add('foo', '/b/');
		$expected->add('bar', '/c/');

		$tag = new Tag;
		$tag->attributePreprocessors->add('baz', '/d/');
		$tag->attributePreprocessors = $attributePreprocessors;

		$this->assertEquals(
			$expected->asConfig(),
			$tag->attributePreprocessors->asConfig()
		);
	}

	/**
	* @testdox $tag->nestingLimit accepts '10' and casts it as an integer
	*/
	public function testNestingLimitString()
	{
		$tag = new Tag;
		$tag->nestingLimit = '10';
		$this->assertSame(10, $tag->nestingLimit);
	}

	/**
	* @testdox $tag->nestingLimit rejects non-numbers
	*/
	public function testNestingLimitNonNumber()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('nestingLimit must be a number greater than 0');

		$tag = new Tag;
		$tag->nestingLimit = 'invalid';
	}

	/**
	* @testdox $tag->nestingLimit rejects numbers less than 1
	*/
	public function testNestingLimitLessThanOne()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('nestingLimit must be a number greater than 0');

		$tag = new Tag;
		$tag->nestingLimit = -1;
	}

	/**
	* @testdox $tag->rules can be assigned a 2D array of rules
	*/
	public function testRulesArray()
	{
		$rules = [
			'allowChild' => ['B'],
			'denyChild'  => ['I']
		];

		$tag = new Tag;
		$tag->rules = $rules;

		$this->assertEquals($rules, iterator_to_array($tag->rules));
	}

	/**
	* @testdox $tag->rules can be assigned an instance of Ruleset to copy its content
	*/
	public function testRulesInstanceOfRuleset()
	{
		$ruleset = new Ruleset;
		$ruleset->allowChild('B');

		$tag = new Tag;
		$tag->rules = $ruleset;

		$this->assertEquals($ruleset, $tag->rules);
		$this->assertNotSame($ruleset, $tag->rules, '$tag->rules should not have been replaced with $ruleset');
	}

	/**
	* @testdox Setting $tag->rules clears previous rules
	* @depends testRulesArray
	*/
	public function testRulesArrayClears()
	{
		$rules = [
			'allowChild' => ['B'],
			'denyChild'  => ['I']
		];

		$tag = new Tag;
		$tag->rules->allowChild('U');
		$tag->rules = $rules;

		$this->assertEquals($rules, iterator_to_array($tag->rules));
	}

	/**
	* @testdox $tag->tagLimit accepts '10' and casts it as an integer
	*/
	public function testTagLimitString()
	{
		$tag = new Tag;
		$tag->tagLimit = '10';
		$this->assertSame(10, $tag->tagLimit);
	}

	/**
	* @testdox $tag->tagLimit rejects non-numbers
	*/
	public function testTagLimitNonNumber()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('tagLimit must be a number greater than 0');

		$tag = new Tag;
		$tag->tagLimit = 'invalid';
	}

	/**
	* @testdox $tag->tagLimit rejects numbers less than 1
	*/
	public function testTagLimitLessThanOne()
	{
		$this->expectException('InvalidArgumentException');
		$this->expectExceptionMessage('tagLimit must be a number greater than 0');

		$tag = new Tag;
		$tag->tagLimit = -1;
	}

	/**
	* @testdox $tag->template = 'foo' set the tag's template to an instance of Template
	*/
	public function testSetTemplate()
	{
		$tag = new Tag;
		$tag->template = 'foo';

		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Items\\Template',
			$this->getObjectProperty($tag, 'template')
		);
	}

	/**
	* @testdox $tag->template returns the tag's template
	*/
	public function testGetTemplate()
	{
		$tag = new Tag;
		$tag->template = 'foo';

		$this->assertSame('foo', (string) $tag->template);
	}

	/**
	* @testdox isset($tag->template) is supported
	*/
	public function testIssetTemplate()
	{
		$tag = new Tag;

		$this->assertFalse(isset($tag->template));
		$tag->template = 'foo';
		$this->assertTrue(isset($tag->template));
	}

	/**
	* @testdox unset($tag->template) is supported
	*/
	public function testUnsetTemplate()
	{
		$tag = new Tag;
		$tag->template = 'foo';

		$this->assertTrue(isset($tag->template));
		unset($tag->template);
		$this->assertFalse(isset($tag->template));
	}

	/**
	* @testdox asConfig() produces a config array, omitting properties that are not needed during parsing such as template
	*/
	public function testAsConfig()
	{
		$tag = new Tag;
		$tag->template     = '';
		$tag->nestingLimit = 3;
		$tag->tagLimit     = 99;

		$config = $tag->asConfig();

		$this->assertArrayHasKey('nestingLimit', $config);
		$this->assertSame(3, $config['nestingLimit']);

		$this->assertArrayHasKey('tagLimit', $config);
		$this->assertSame(99, $config['tagLimit']);

		$this->assertArrayNotHasKey('template', $config);
	}

	/**
	* @testdox $tag->filterChain starts with FilterProcessing::executeAttributePreprocessors by default
	*/
	public function testFilterChain1()
	{
		$callback = 's9e\\TextFormatter\\Parser\\FilterProcessing::executeAttributePreprocessors';

		$tag = new Tag;
		$this->assertSame($callback, $tag->filterChain[0]->getCallback());
	}

	/**
	* @testdox $tag->filterChain contains FilterProcessing::filterAttributes by default
	*/
	public function testFilterChain2()
	{
		$callback = 's9e\\TextFormatter\\Parser\\FilterProcessing::filterAttributes';

		$tag = new Tag;
		$this->assertSame($callback, $tag->filterChain[1]->getCallback());
	}

	/**
	* @testdox asConfig() omits 'FilterProcessing::executeAttributePreprocessors' from the returned filterChain if no attribute preprocessor is defined
	*/
	public function testFilterChainConfigOmitsUnusedFilter()
	{
		$tag = new Tag;
		$tag->attributes->add('foo');

		$config = $tag->asConfig();

		foreach ($config['filterChain'] as $filter)
		{
			$this->assertStringNotContainsString('executeAttributePreprocessors', $filter['callback']);
		}
	}

	/**
	* @testdox asConfig() does not modify the tag's filterChain itself
	*/
	public function testFilterChainConfigIsSafe()
	{
		$tag = new Tag;
		$config = $tag->asConfig();

		$tag->attributes->add('foo');
		$config = $tag->asConfig();

		$this->assertArrayHasKey('filterChain', $config);
	}
}