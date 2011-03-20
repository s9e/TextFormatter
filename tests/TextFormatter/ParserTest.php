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

	protected function assertParsing($text, $expectedXml, $expectedLog = array('error' => null))
	{
		$parser    = $this->cb->getParser();
		$actualXml = $parser->parse($text);

		$this->assertSame($expectedXml, $actualXml);
		$this->assertArrayMatches($expectedLog, $parser->getLog());
	}

	public function testFulfilledRequireParentRuleAllowsTag()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a][b]stuff[/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st>stuff<et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	public function testFulfilledRequireParentRuleAllowsTagDespitePrefix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a:123][b]stuff[/b][/a:123]',
			'<rt><A><st>[a:123]</st><B><st>[b]</st>stuff<et>[/b]</et></B><et>[/a:123]</et></A></rt>'
		);
	}

	public function testUnfulfilledRequireParentRuleBlocksTag()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[b]stuff[/b]',
			'<pt>[b]stuff[/b]</pt>',
			array(
				'error' => array(
					array(
						'pos'     => 0,
						'msg'     => 'Tag %1$s requires %2$s as parent',
						'params'  => array('B', 'A'),
						'tagName' => 'B'
					)
				)
			)
		);
	}

	public function testUnfulfilledRequireParentRuleBlocksTagDespiteAscendant()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->BBCodes->addBBCode('c');
		$this->cb->addTagRule('b', 'requireParent', 'a');

		$this->assertParsing(
			'[a][c][b]stuff[/b][/c][/a]',
			'<rt><A><st>[a]</st><C><st>[c]</st>[b]stuff[/b]<et>[/c]</et></C><et>[/a]</et></A></rt>',
			array(
				'error' => array(
					array(
						'pos'     => 6,
						'msg'     => 'Tag %1$s requires %2$s as parent',
						'params'  => array('B', 'A'),
						'tagName' => 'B'
					)
				)
			)
		);
	}

	public function testCloseParentRuleIsApplied()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p]one[p]two',
			'<rt><P><st>[p]</st>one</P><P><st>[p]</st>two</P></rt>'
		);
	}

	/**
	* @depends testCloseParentRuleIsApplied
	*/
	public function testCloseParentRuleIsAppliedOnTagWithIdenticalSuffix()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p:123]one[p:123]two',
			'<rt><P><st>[p:123]</st>one</P><P><st>[p:123]</st>two</P></rt>'
		);
	}

	/**
	* @depends testCloseParentRuleIsApplied
	*/
	public function testCloseParentRuleIsAppliedOnTagWithDifferentSuffix()
	{
		$this->cb->BBCodes->addBBCode('p');
		$this->cb->addTagRule('p', 'closeParent', 'p');

		$this->assertParsing(
			'[p:123]one[p:456]two',
			'<rt><P><st>[p:123]</st>one</P><P><st>[p:456]</st>two</P></rt>'
		);
	}

	public function testDenyRuleBlocksTag()
	{
		$this->cb->BBCodes->addBBCode('a', array('defaultRule' => 'allow'));
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('a', 'deny', 'b');

		$this->assertParsing(
			'[a]..[b][/b]..[/a]',
			'<rt><A><st>[a]</st>..[b][/b]..<et>[/a]</et></A></rt>'
		);
	}

	public function testAllowRuleAllowsTag()
	{
		$this->cb->BBCodes->addBBCode('a', array('defaultRule' => 'deny'));
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('a', 'allow', 'b');

		$this->assertParsing(
			'[a][b][/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	public function testRequireAscendantRuleIsFulfilledByParent()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a][b][/b][/a]',
			'<rt><A><st>[a]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a]</et></A></rt>'
		);
	}

	/**
	* @depends testRequireAscendantRuleIsFulfilledByParent
	*/
	public function testRequireAscendantRuleIsFulfilledByParentWithSuffix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a:123][b][/b][/a:123]',
			'<rt><A><st>[a:123]</st><B><st>[b]</st><et>[/b]</et></B><et>[/a:123]</et></A></rt>'
		);
	}

	public function testRequireAscendantRuleIsFulfilledByAscendant()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->BBCodes->addBBCode('c');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a][c][b][/b][/c][/a]',
			'<rt><A><st>[a]</st><C><st>[c]</st><B><st>[b]</st><et>[/b]</et></B><et>[/c]</et></C><et>[/a]</et></A></rt>'
		);
	}

	/**
	* @depends testRequireAscendantRuleIsFulfilledByAscendant
	*/
	public function testRequireAscendantRuleIsFulfilledByAscendantWithSuffix()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->BBCodes->addBBCode('c');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[a:123][c][b][/b][/c][/a:123]',
			'<rt><A><st>[a:123]</st><C><st>[c]</st><B><st>[b]</st><et>[/b]</et></B><et>[/c]</et></C><et>[/a:123]</et></A></rt>'
		);
	}

	public function testUnfulfilledRequireAscendantRuleBlocksTag()
	{
		$this->cb->BBCodes->addBBCode('a');
		$this->cb->BBCodes->addBBCode('b');
		$this->cb->addTagRule('b', 'requireAscendant', 'a');

		$this->assertParsing(
			'[b]stuff[/b]',
			'<pt>[b]stuff[/b]</pt>',
			array(
				'error' => array(
					array(
						'pos'     => 0,
						'msg'     => 'Tag %1$s requires %2$s as ascendant',
						'params'  => array('B', 'A'),
						'tagName' => 'B'
					)
				)
			)
		);
	}

}