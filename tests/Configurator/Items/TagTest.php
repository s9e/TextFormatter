<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Configurator\Collections\AttributePreprocessorCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TemplateCollection;
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

		$tag = new Tag;
		$tag->attributePreprocessors = $attributePreprocessors;

		$config = $tag->attributePreprocessors->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertEquals(
			$attributePreprocessors,
			$config
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

		$tag = new Tag;
		$tag->attributePreprocessors->add('baz', '/d/');
		$tag->attributePreprocessors = $attributePreprocessors;

		$config = $tag->attributePreprocessors->asConfig();
		ConfigHelper::filterVariants($config);

		$this->assertSame(
			$attributePreprocessors,
			$config
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
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage nestingLimit must be a number greater than 0
	*/
	public function testNestingLimitNonNumber()
	{
		$tag = new Tag;
		$tag->nestingLimit = 'invalid';
	}

	/**
	* @testdox $tag->nestingLimit rejects numbers less than 1
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage nestingLimit must be a number greater than 0
	*/
	public function testNestingLimitLessThanOne()
	{
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
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage tagLimit must be a number greater than 0
	*/
	public function testTagLimitNonNumber()
	{
		$tag = new Tag;
		$tag->tagLimit = 'invalid';
	}

	/**
	* @testdox $tag->tagLimit rejects numbers less than 1
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage tagLimit must be a number greater than 0
	*/
	public function testTagLimitLessThanOne()
	{
		$tag = new Tag;
		$tag->tagLimit = -1;
	}

	/**
	* @testdox $tag->templates can be assigned an array of templates
	*/
	public function testTemplatesArray()
	{
		$templates = [
			''     => 'first',
			'@foo' => 'second'
		];

		$tag = new Tag;
		$tag->templates = $templates;

		$this->assertEquals($templates, iterator_to_array($tag->templates));
	}

	/**
	* @testdox $tag->templates can be assigned an instance of TemplateCollection to copy its content
	*/
	public function testTemplatesInstanceOfTemplateCollection()
	{
		$tag = new Tag;

		$templates = new TemplateCollection;
		$templates->set('', 'foo');

		$tag->templates = $templates;

		$this->assertEquals($templates, $tag->templates);
		$this->assertNotSame($templates, $tag->templates, '$tag->templates should not have been replaced with $templates');
	}

	/**
	* @testdox Setting $tag->templates clears previous templates
	* @depends testTemplatesArray
	*/
	public function testTemplatesArrayClears()
	{
		$templates = [
			''     => 'first',
			'@foo' => 'second'
		];

		$tag = new Tag;
		$tag->templates = ['' => 'deleteme'];
		$tag->templates = $templates;

		$this->assertEquals($templates, iterator_to_array($tag->templates));
	}

	/**
	* @testdox setTemplates() throws an InvalidArgumentException if its argument is not an array or an instance of TemplateCollection
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage setTemplates() expects an array or an instance of TemplateCollection
	*/
	public function testSetTemplatesInvalid()
	{
		$tag = new Tag;
		$tag->templates = false;
	}

	/**
	* @testdox $tag->defaultTemplate maps to $tag->templates->get('')
	*/
	public function testGetDefaultTemplate()
	{
		$tag = new Tag;
		$tag->templates->set('', 'foo');

		$this->assertSame('foo', (string) $tag->defaultTemplate);
	}

	/**
	* @testdox $tag->defaultTemplate = 'foo' maps to $tag->templates->set('', 'foo')
	*/
	public function testSetDefaultTemplate()
	{
		$tag = new Tag;
		$tag->defaultTemplate = 'foo';

		$this->assertSame('foo', (string) $tag->templates->get(''));
	}

	/**
	* @testdox unset($tag->defaultTemplate) is supported
	*/
	public function testUnsetDefaultTemplate()
	{
		$tag = new Tag;
		$tag->templates->set('', 'foo');

		$this->assertTrue(isset($tag->templates['']));
		unset($tag->defaultTemplate);
		$this->assertFalse(isset($tag->templates['']));
	}

	/**
	* @testdox asConfig() produces a config array, omitting properties that are not needed during parsing: defaultChildRule, defaultDescendantRule and templates
	*/
	public function testAsConfig()
	{
		$tag = new Tag;
		$tag->defaultChildRule      = 'allow';
		$tag->defaultDescendantRule = 'allow';
		$tag->defaultTemplate       = '';
		$tag->nestingLimit          = 3;
		$tag->tagLimit              = 99;

		$config = $tag->asConfig();

		$this->assertArrayHasKey('nestingLimit', $config);
		$this->assertSame(3, $config['nestingLimit']);

		$this->assertArrayHasKey('tagLimit', $config);
		$this->assertSame(99, $config['tagLimit']);

		$this->assertArrayNotHasKey('defaultChildRule', $config);
		$this->assertArrayNotHasKey('defaultDescendantRule', $config);
		$this->assertArrayNotHasKey('templates', $config);
	}

	/**
	* @testdox $tag->filterChain starts with Parser::executeAttributePreprocessors by default
	*/
	public function testFilterChainDefault1()
	{
		$callback = 's9e\\TextFormatter\\Parser::executeAttributePreprocessors';

		$tag = new Tag;
		$this->assertSame($callback, $tag->filterChain[0]->getCallback());
	}

	/**
	* @testdox $tag->filterChain contains Parser::filterAttributes by default
	*/
	public function testFilterChainDefault2()
	{
		$callback = 's9e\\TextFormatter\\Parser::filterAttributes';

		$tag = new Tag;
		$this->assertSame($callback, $tag->filterChain[1]->getCallback());
	}

	/**
	* @testdox asConfig() omits 'Parser::executeAttributePreprocessors' from the returned filterChain if no attribute preprocessor is defined
	*/
	public function testFilterChainConfigOmitsUnusedFilter()
	{
		$tag = new Tag;
		$tag->attributes->add('foo');

		$config = $tag->asConfig();

		foreach ($config['filterChain'] as $filter)
		{
			$this->assertNotContains('Parser::executeAttributePreprocessors', $filter['callback']);
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