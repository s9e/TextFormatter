<?php declare(strict_types=1);

namespace s9e\TextFormatter\Tests\Plugins\TaskLists;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\TaskLists\Helper;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\TaskLists\Helper
*/
class HelperTest extends Test
{
	public function setUp(): void
	{
		parent::setUp();
		$this->configurator->TaskLists;
	}

	/**
	* @dataProvider getGetStatsTests
	*/
	public function testGetStats($text, $expected)
	{
		$xml = $this->getParser()->parse(implode("\n", $text));

		$this->assertEquals($expected, Helper::getStats($xml));
	}

	public function getGetStatsTests()
	{
		return [
			[
				[
					'...'
				],
				[
					'complete'   => 0,
					'incomplete' => 0
				]
			],
			[
				[
					'- [x] checked',
					'- [X] Checked',
					'- [ ] unchecked'
				],
				[
					'complete'   => 2,
					'incomplete' => 1
				]
			],
		];
	}

	/**
	* @testdox getStats() counts custom states
	*/
	public function testGetStatsCustom()
	{
		$xml      = '<r><TASK id="" state="custom"/></r>';
		$expected = ['complete' => 0, 'custom' => 1, 'incomplete' => 0];

		$this->assertEquals($expected, Helper::getStats($xml));
	}

	/**
	* @dataProvider getMarkTaskStateTests
	*/
	public function testMarkTaskState($methodName, $xml, $id, $expected)
	{
		$xml      = implode("\n", $xml);
		$expected = implode("\n", $expected);

		$this->assertEquals($expected, Helper::$methodName($xml, $id));
	}

	public function getMarkTaskStateTests()
	{
		return [
			[
				'markTaskComplete',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="incomplete">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				'345',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="complete">[x]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				'markTaskIncomplete',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="incomplete">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				'234',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="incomplete">[ ]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="incomplete">[ ]</TASK> unchecked</LI></LIST></r>'
				],
			],
			[
				'markTaskComplete',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="incomplete">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				'234',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[x]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="incomplete">[ ]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				'markTaskComplete',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="custom">[?]</TASK> unchecked</LI></LIST></r>'
				],
				'345',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="complete">[x]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				'markTaskComplete',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="incomplete">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				'111',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="incomplete">[ ]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				'markTaskComplete',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK a="a" b="b" id="345" z="z">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				'345',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="complete">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="complete">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK a="a" b="b" id="345" state="complete" z="z">[x]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				'markTaskComplete',
				[
					'<r><TASK id="123">[ ]</TASK></r>'
				],
				'123',
				[
					'<r><TASK id="123" state="complete">[x]</TASK></r>'
				]
			],
			[
				'markTaskComplete',
				[
					// Cannot happen under normal circumstances
					'<r><TASK id="123"/></r>'
				],
				'123',
				[
					'<r><TASK id="123"/></r>'
				]
			],
		];
	}
}