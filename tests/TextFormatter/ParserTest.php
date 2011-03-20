<?php

namespace s9e\Toolkit\Tests;

use s9e\Toolkit\TextFormatter\ConfigBuilder,
    s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\Renderer,
    s9e\Toolkit\TextFormatter\Plugins\EmoticonsConfig;

include_once __DIR__ . '/../../src/TextFormatter/ConfigBuilder.php';
include_once __DIR__ . '/../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Parser
*/
class ParserTest extends Test
{
	public function setUp()
	{
		$this->cb = new ConfigBuilder;
	}

	public function testRequireParentRuleIsApplied()
	{
	}

	/**
	* @dataProvider getRulesTestData
	*/
	public function testRulesAreApplied($bbcodeName, $text, $expected, $expectedLog = array())
	{
		$this->cb->BBCodes->addPredefinedBBCode($bbcodeName);

		$parser = $this->cb->getParser();
		$actual = $parser->parse($text);

		$this->assertSame($expected, $actual);

		$this->assertArrayMatches($parser->getLog(), $expectedLog);
	}

	public function getRulesTestData()
	{
		return array(
			// requireParent
			array(
				'LIST',
				'[*]list item',
				'<pt>[*]list item</pt>',
				array(
					'error' => array(
						array(
							'pos'     => 0,
							'msg'     => 'Tag %1$s requires %2$s as parent',
							'params'  => array('LI', 'LIST'),
							'tagName' => 'LI'
						)
					)
				)
			),
			// closeParent
			array(
				'LIST',
				'[list][*]one[*]two[/list]',
				'<rt><LIST style="disc"><st>[list]</st><LI><st>[*]</st>one</LI><LI><st>[*]</st>two</LI><et>[/list]</et></LIST></rt>'
			),
			// closeParent with tag suffixes
			array(
				'LIST',
				'[list][*:123]one[*:456]two[/list]',
				'<rt><LIST style="disc"><st>[list]</st><LI><st>[*:123]</st>one</LI><LI><st>[*:456]</st>two</LI><et>[/list]</et></LIST></rt>'
			),
		);
	}
}