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

	protected function getParser()
	{
		return $this->cb->getParser();
	}

	/**
	* @dataProvider getRulesTestData
	*/
	public function testRulesAreApplied($bbcodeName, $text, $expected, $expectedLog = array())
	{
		$this->cb->BBCodes->addPredefinedBBCode($bbcodeName);

		$parser = $this->cb->getParser();
		$xml = $parser->parse($text);

		$actual = $this->cb->getRenderer()->render($xml);
		$this->assertSame($expected, $actual);

		$this->assertArrayMatches($parser->getLog(), $expectedLog);
	}

	public function getRulesTestData()
	{
		return array(
			array(
				'LIST',
				'[*]list item',
				'[*]list item',
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
			)
		);
	}
}