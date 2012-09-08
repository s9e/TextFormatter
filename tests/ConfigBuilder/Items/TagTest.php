<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Items;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\Ruleset;
use s9e\TextFormatter\ConfigBuilder\Collections\Templateset;
use s9e\TextFormatter\ConfigBuilder\Items\Tag;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Items\Tag
*/
class TagTest extends Test
{
	/**
	* @testdox An array of options can be passed to the constructor
	*/
	public function testConstructorOptions()
	{
		$tag = new Tag(array('nestingLimit' => 123));
		$this->assertSame(123, $tag->nestingLimit);
	}

	/**
	* @testdox $tag->defaultChildRule accepts 'allow'
	*/
	public function testDefaultChildRuleAllow()
	{
		$tag = new Tag;
		$tag->defaultChildRule = 'allow';
		$this->assertSame('allow', $tag->defaultChildRule);
	}

	/**
	* @testdox $tag->defaultChildRule accepts 'deny'
	*/
	public function testDefaultChildRuleDeny()
	{
		$tag = new Tag;
		$tag->defaultChildRule = 'deny';
		$this->assertSame('deny', $tag->defaultChildRule);
	}

	/**
	* @testdox $tag->defaultChildRule throws an exception if set to anything else than 'allow' or 'deny'
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage defaultChildRule must be either 'allow' or 'deny'
	*/
	public function testDefaultChildRuleInvalid()
	{
		$tag = new Tag;
		$tag->defaultChildRule = 'invalid';
	}

	/**
	* @testdox $tag->defaultDescendantRule accepts 'allow'
	*/
	public function testDefaultDescendantRuleAllow()
	{
		$tag = new Tag;
		$tag->defaultDescendantRule = 'allow';
		$this->assertSame('allow', $tag->defaultDescendantRule);
	}

	/**
	* @testdox $tag->defaultDescendantRule accepts 'deny'
	*/
	public function testDefaultDescendantRuleDeny()
	{
		$tag = new Tag;
		$tag->defaultDescendantRule = 'deny';
		$this->assertSame('deny', $tag->defaultDescendantRule);
	}

	/**
	* @testdox $tag->defaultDescendantRule throws an exception if set to anything else than 'allow' or 'deny'
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage defaultDescendantRule must be either 'allow' or 'deny'
	*/
	public function testDefaultDescendantRuleInvalid()
	{
		$tag = new Tag;
		$tag->defaultDescendantRule = 'invalid';
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
		$rules = array(
			'allowChild' => array('B'),
			'denyChild'  => array('I')
		);

		$tag = new Tag;
		$tag->rules = $rules;

		$this->assertEquals($rules, iterator_to_array($tag->rules));
	}

	/**
	* @testdox Setting $tag->rules clears previous rules
	* @depends testRulesArray
	*/
	public function testRulesArrayClears()
	{
		$rules = array(
			'allowChild' => array('B'),
			'denyChild'  => array('I')
		);

		$tag = new Tag;
		$tag->rules->allowChild('U');
		$tag->rules = $rules;

		$this->assertEquals($rules, iterator_to_array($tag->rules));
	}

	/**
	* @testdox $tag->rules can be replaced with an instance of Ruleset
	*/
	public function testRulesInstanceOfRuleset()
	{
		$ruleset = new Ruleset;

		$tag = new Tag;
		$tag->rules = $ruleset;

		$this->assertSame($ruleset, $tag->rules);
	}

	/**
	* @testdox setRules() throws an InvalidArgumentException if its argument is not an array or an instance of Ruleset
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage setRules() expects an array or an instance of Ruleset
	*/
	public function testSetRulesInvalid()
	{
		$tag = new Tag;
		$tag->rules = false;
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
		$templates = array(
			''     => 'first',
			'@foo' => 'second'
		);

		$tag = new Tag;
		$tag->templates = $templates;

		$this->assertEquals($templates, iterator_to_array($tag->templates));
	}

	/**
	* @testdox Setting $tag->templates clears previous templates
	* @depends testTemplatesArray
	*/
	public function testTemplatesArrayClears()
	{
		$templates = array(
			'allowChild' => array('B'),
			'denyChild'  => array('I')
		);

		$tag = new Tag;
		$tag->templates->allowChild('U');
		$tag->templates = $templates;

		$this->assertEquals($templates, iterator_to_array($tag->templates));
	}

	/**
	* @testdox $tag->templates can be replaced with an instance of Templateset
	*/
	public function testTemplatesInstanceOfTemplateset()
	{
		$templateset = new Templateset;

		$tag = new Tag;
		$tag->templates = $templateset;

		$this->assertSame($templateset, $tag->templates);
	}

	/**
	* @testdox setTemplates() throws an InvalidArgumentException if its argument is not an array or an instance of Templateset
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage setTemplates() expects an array or an instance of Templateset
	*/
	public function testSetTemplatesInvalid()
	{
		$tag = new Tag;
		$tag->templates = false;
	}

}