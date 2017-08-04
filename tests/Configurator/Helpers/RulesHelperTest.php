<?php

namespace s9e\TextFormatter\Tests\Configurator\Helpers;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Helpers\RulesHelper;

/**
* @covers s9e\TextFormatter\Configurator\Helpers\RulesHelper
*/
class RulesHelperTest extends Test
{
	/**
	* @testdox Works with a single tag
	*/
	public function testSingleTag()
	{
		$tags = new TagCollection;
		$tags->add('A')->rules->allowChild('A');

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');

		$this->assertEquals(
			[
				'root' => ['allowed' => [1]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [1]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox If no rule is defined, the default is to deny children and descendants
	*/
	public function testDefaultIsDeny()
	{
		$tags = new TagCollection;
		$tags->add('A');

		$this->assertEquals(
			[
				'root' => ['allowed' => [0]],
				'tags' => []
			],
			RulesHelper::getBitfields($tags, new Ruleset)
		);
	}

	/**
	* @testdox Works with multiple tags
	*/
	public function testTwoTags()
	{
		$tags = new TagCollection;

		$a = $tags->add('A');
		$a->rules->allowChild('A');
		$a->rules->allowChild('B');

		$b = $tags->add('B');
		$b->rules->allowChild('B');

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');
		$rootRules->allowChild('B');

		$this->assertEquals(
			[
				'root' => ['allowed' => [0b11]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [0b11]
					],
					'B' => [
						'bitNumber' => 1,
						'allowed'   => [0b10]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox denyChild overrides allowChild
	*/
	public function testDenyChildOverridesAllowChild()
	{
		$tags = new TagCollection;

		$tag = $tags->add('A');
		$tag->rules->denyChild('A');
		$tag->rules->allowChild('A');

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');

		$this->assertEquals(
			[
				'root' => ['allowed' => [1]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [0]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox denyDescendant does not override allowChild
	*/
	public function testDenyDescendantDoesNotOverrideAllowChild()
	{
		$tags = new TagCollection;

		$tag = $tags->add('A');
		$tag->rules->allowChild('A');
		$tag->rules->denyDescendant('A');

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');

		$this->assertEquals(
			[
				'root' => ['allowed' => [1]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [1]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox allowDescendant does not override denyChild
	*/
	public function testAllowDescendantDoesNotOverrideDenyChild()
	{
		$tags = new TagCollection;

		$tag = $tags->add('A');
		$tag->rules->allowDescendant('A');
		$tag->rules->denyChild('A');

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');

		$this->assertEquals(
			[
				'root' => ['allowed' => [1]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [0b100000000]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox denyDescendant overrides allowDescendant
	*/
	public function testDenyDescendantOverridesAllowDescendant()
	{
		$tags = new TagCollection;

		$tag = $tags->add('A');
		$tag->rules->denyDescendant('A');
		$tag->rules->allowDescendant('A');

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');

		$this->assertEquals(
			[
				'root' => ['allowed' => [1]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [0]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox ignoreTags(true) overrides everything
	*/
	public function testIgnoreTagsPositive()
	{
		$tags = new TagCollection;

		$tag = $tags->add('A');
		$tag->rules->allowChild('A');
		$tag->rules->allowDescendant('A');
		$tag->rules->ignoreTags(true);

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');

		$this->assertEquals(
			[
				'root' => ['allowed' => [1]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [0]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox ignoreTags(false) has no effect
	*/
	public function testIgnoreTagsNegative()
	{
		$tags = new TagCollection;

		$tag = $tags->add('A');
		$tag->rules->allowChild('A');
		$tag->rules->allowDescendant('A');
		$tag->rules->ignoreTags(false);

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');

		$this->assertEquals(
			[
				'root' => ['allowed' => [1]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [0b100000001]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox Tags with a requireParent rule are not allowed at the root
	*/
	public function testRequireParentDisallowAtRoot()
	{
		$tags = new TagCollection;

		$a = $tags->add('A');
		$a->rules->allowChild('A');
		$a->rules->allowChild('B');

		$b = $tags->add('B');
		$b->rules->allowChild('A');
		$b->rules->allowChild('B');
		$b->rules->requireParent('A');

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');
		$rootRules->allowChild('B');

		$this->assertEquals(
			[
				'root' => ['allowed' => [1]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [0b11]
					],
					'B' => [
						'bitNumber' => 1,
						'allowed'   => [1]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox Tags that aren't allowed anywhere are omitted from the return array
	*/
	public function testUnusedTag()
	{
		$tags = new TagCollection;

		$a = $tags->add('A');
		$a->rules->allowChild('A');

		$b = $tags->add('B');
		$b->rules->allowChild('A');
		$b->rules->allowChild('B');

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');

		$this->assertEquals(
			[
				'root' => ['allowed' => [1]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [1]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox Tags that are allowed in a closed dependency loop are omitted from the return array
	*/
	public function testUnusedTagsInLoop()
	{
		$tags = new TagCollection;

		$a = $tags->add('A');
		$a->rules->allowChild('A');
		$a->rules->allowChild('B');

		$b = $tags->add('B');
		$b->rules->allowChild('A');
		$b->rules->allowChild('B');

		$this->assertEquals(
			[
				'root' => ['allowed' => [0]],
				'tags' => []
			],
			RulesHelper::getBitfields($tags, new Ruleset)
		);
	}

	/**
	* @testdox Rules targeting inexistent tags do not interfere
	*/
	public function testInexistentTag()
	{
		$tags = new TagCollection;

		$a = $tags->add('A');
		$a->rules->allowChild('A');
		$a->rules->allowChild('B');
		$a->rules->allowChild('C');

		$b = $tags->add('B');
		$b->rules->allowChild('B');
		$b->rules->allowChild('C');

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');
		$rootRules->allowChild('B');
		$rootRules->allowChild('C');

		$this->assertEquals(
			[
				'root' => ['allowed' => [0b11]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [0b11]
					],
					'B' => [
						'bitNumber' => 1,
						'allowed'   => [0b10]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}

	/**
	* @testdox Bitfields are compressed by making tags that are targeted by the same permissions share the same bit number
	*/
	public function testTwoIdenticalTags()
	{
		$tags = new TagCollection;

		$a = $tags->add('A');
		$a->rules->allowChild('A');
		$a->rules->allowChild('B');

		$b = $tags->add('B');
		$b->rules->allowChild('A');
		$b->rules->allowChild('B');

		$rootRules = new Ruleset;
		$rootRules->allowChild('A');
		$rootRules->allowChild('B');

		$this->assertEquals(
			[
				'root' => ['allowed' => [1]],
				'tags' => [
					'A' => [
						'bitNumber' => 0,
						'allowed'   => [1]
					],
					'B' => [
						'bitNumber' => 0,
						'allowed'   => [1]
					]
				]
			],
			RulesHelper::getBitfields($tags, $rootRules)
		);
	}
}