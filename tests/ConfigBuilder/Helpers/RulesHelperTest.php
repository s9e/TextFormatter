<?php

namespace s9e\TextFormatter\Tests\ConfigBuilder\Helpers;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\ConfigBuilder\Collections\TagCollection;
use s9e\TextFormatter\ConfigBuilder\Helpers\RulesHelper;

/**
* @covers s9e\TextFormatter\ConfigBuilder\Helpers\RulesHelper
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

		$this->assertEquals(
			array(
				'rootContext' => array(
					'allowedChildren'    => "\x01",
					'allowedDescendants' => "\x01"
				),
				'tags' => array(
					'A' => array(
						'bitNumber'          => 0,
						'allowedChildren'    => "\x01",
						'allowedDescendants' => "\x01"
					)
				)
			),
			RulesHelper::getBitfields($tags)
		);
	}

	/**
	* @testdox If no rule is defined, the default is to allow children and descendants
	*/
	public function testDefaultIsAllow()
	{
		$tags = new TagCollection;

		$tags->add('A');

		$this->assertEquals(
			array(
				'rootContext' => array(
					'allowedChildren'    => "\x01",
					'allowedDescendants' => "\x01"
				),
				'tags' => array(
					'A' => array(
						'bitNumber'          => 0,
						'allowedChildren'    => "\x01",
						'allowedDescendants' => "\x01"
					)
				)
			),
			RulesHelper::getBitfields($tags)
		);
	}

	/**
	* @testdox defaultChildRule is correctly applied
	*/
	public function testDefaultChildRuleIsApplied()
	{
		$tags = new TagCollection;

		$tags->add('A')->rules->defaultChildRule('deny');

		$this->assertEquals(
			array(
				'rootContext' => array(
					'allowedChildren'    => "\x01",
					'allowedDescendants' => "\x01"
				),
				'tags' => array(
					'A' => array(
						'bitNumber'          => 0,
						'allowedChildren'    => "\x00",
						'allowedDescendants' => "\x01"
					)
				)
			),
			RulesHelper::getBitfields($tags)
		);
	}

	/**
	* @testdox defaultDescendantRule is correctly applied
	*/
	public function testDefaultDescendantRuleIsApplied()
	{
		$tags = new TagCollection;

		$tags->add('A')->rules->defaultDescendantRule('deny');

		$this->assertEquals(
			array(
				'rootContext' => array(
					'allowedChildren'    => "\x01",
					'allowedDescendants' => "\x01"
				),
				'tags' => array(
					'A' => array(
						'bitNumber'          => 0,
						'allowedChildren'    => "\x00",
						'allowedDescendants' => "\x00"
					)
				)
			),
			RulesHelper::getBitfields($tags)
		);
	}

	public function testTwoTags()
	{
		$tags = new TagCollection;

		$tags->add('A');
		$tags->add('B')->rules->denyChild('A');

		$this->assertEquals(
			array(
				'rootContext' => array(
					'allowedChildren'    => "\x03",
					'allowedDescendants' => "\x03"
				),
				'tags' => array(
					'A' => array(
						'bitNumber'          => 0,
						'allowedChildren'    => "\x03",
						'allowedDescendants' => "\x03"
					),
					'B' => array(
						'bitNumber'          => 1,
						'allowedChildren'    => "\x02",
						'allowedDescendants' => "\x03"
					)
				)
			),
			RulesHelper::getBitfields($tags)
		);
	}
}