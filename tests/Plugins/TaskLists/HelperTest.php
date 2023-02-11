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

	public static function getGetStatsTests()
	{
		return [
			[
				[
					'...'
				],
				[
					'checked'   => 0,
					'unchecked' => 0
				]
			],
			[
				[
					'- [x] checked',
					'- [X] Checked',
					'- [ ] unchecked'
				],
				[
					'checked'   => 2,
					'unchecked' => 1
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
		$expected = ['checked' => 0, 'custom' => 1, 'unchecked' => 0];

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

	public static function getMarkTaskStateTests()
	{
		return [
			[
				'checkTask',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				'345',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="checked">[x]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				'uncheckTask',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				'234',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="unchecked">[ ]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				],
			],
			[
				'checkTask',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				'234',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[x]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				'checkTask',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="custom">[?]</TASK> unchecked</LI></LIST></r>'
				],
				'345',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="checked">[x]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				'checkTask',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				'111',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK id="345" state="unchecked">[ ]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				'checkTask',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK a="a" b="b" id="345" z="z">[ ]</TASK> unchecked</LI></LIST></r>'
				],
				'345',
				[
					'<r><LIST><LI><s>- </s><TASK id="123" state="checked">[x]</TASK> checked</LI>',
					'<LI><s>- </s><TASK id="234" state="checked">[X]</TASK> Checked</LI>',
					'<LI><s>- </s><TASK a="a" b="b" id="345" state="checked" z="z">[x]</TASK> unchecked</LI></LIST></r>'
				]
			],
			[
				'checkTask',
				[
					'<r><TASK id="123">[ ]</TASK></r>'
				],
				'123',
				[
					'<r><TASK id="123" state="checked">[x]</TASK></r>'
				]
			],
			[
				'checkTask',
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