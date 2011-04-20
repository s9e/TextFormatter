<?php

namespace s9e\Toolkit\Tests\TextFormatter\Plugins;

use s9e\Toolkit\Tests\Test,
    s9e\Toolkit\TextFormatter\Parser;

include_once __DIR__ . '/../../Test.php';

/**
* @covers s9e\Toolkit\TextFormatter\Plugins\BBCodesParser
*/
class BBCodesParserTest extends Test
{
	public function testSimpleBbcodesAreParsed()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[B]bold[/B]',
			'<rt><B><st>[B]</st>bold<et>[/B]</et></B></rt>'
		);
	}

	/**
	* @depends testSimpleBbcodesAreParsed
	*/
	public function testBbcodeNamesAreCaseInsensitive()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[b]bold[/B]',
			'<rt><B><st>[b]</st>bold<et>[/B]</et></B></rt>'
		);
	}

	/**
	* @depends testSimpleBbcodesAreParsed
	*/
	public function testBbcodesRemovedFromTheConfigAreIgnored()
	{
		$this->cb->BBCodes->addBBCode('B');

		$parserConfig = $this->cb->getParserConfig();
		unset($parserConfig['plugins']['BBCodes']['bbcodesConfig']['B']);

		$this->parser = new Parser($parserConfig);

		$this->assertParsing(
			'[B]bold[/B]',
			'<pt>[B]bold[/B]</pt>'
		);
	}

	public function testOverlappingTagsAreSortedOut()
	{
		$this->cb->BBCodes->addBBCode(
			'x',
			array('attrs' => array('foo' => array('type' => 'text')))
		);

		$this->assertParsing(
			'[x foo="[b]bar[/b]" /]',
			'<rt><X foo="[b]bar[/b]">[x foo=&quot;[b]bar[/b]&quot; /]</X></rt>'
		);
	}

	public function testBbcodeTagsCanUseAColonFollowedByDigitsAsASuffixToControlHowStartTagsAndEndTagsArePaired()
	{
		$this->cb->BBCodes->addBBCode('B', array('nestingLimit' => 1));

		$this->assertParsing(
			'[B:123]bold tags: [B]text[/B][/B:123]',
			'<rt><B><st>[B:123]</st>bold tags: [B]text[/B]<et>[/B:123]</et></B></rt>'
		);
	}

	/**
	* @depends testSimpleBbcodesAreParsed
	*/
	public function testBbcodeTagsCanBeUsedAsSingletonsLikeSelfClosingXmlTags()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[B/]',
			'<rt><B>[B/]</B></rt>'
		);
	}

	/**
	* @depends testBbcodeTagsCanBeUsedAsSingletonsLikeSelfClosingXmlTags
	*/
	public function testWhitespaceInsideBbcodesIsIgnored()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[B  /]',
			'<rt><B>[B  /]</B></rt>'
		);
	}

	/**
	* @depends testBbcodeTagsCanBeUsedAsSingletonsLikeSelfClosingXmlTags
	*/
	public function testJunkAfterTheSlashOfASelfClosingBbcodeTagGeneratesAWarning()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[B /z]',
			'<pt>[B /z]</pt>',
			array(
				'warning' => array(
					array(
						'pos'    => 4,
						'msg'    => 'Unexpected character: expected $1%s found $2%s',
						'params' => array(']', 'z')
					)
				)
			)
		);
	}

	/**
	* @depends testSimpleBbcodesAreParsed
	*/
	public function testJunkAfterTheNameOfAClosingBbcodeTagGeneratesAWarning()
	{
		$this->cb->BBCodes->addBBCode('B');

		$this->assertParsing(
			'[B]xxx[/B ]',
			'<rt><B><st>[B]</st>xxx[/B ]</B></rt>',
			array(
				'warning' => array(
					array(
						'pos'    => 9,
						'msg'    => 'Unexpected character: expected $1%s found $2%s',
						'params' => array(']', ' ')
					)
				)
			)
		);
	}

	public function testABbcodeCanMapToATagOfADifferentName()
	{
		$this->cb->BBCodes->addBBCode('X', array('tagName' => 'Z'));

		$this->assertParsing(
			'[X/]',
			'<rt><Z>[X/]</Z></rt>'
		);
	}

	public function testAnEqualSignFollowingTheTagNameDefinesTheValueOfTheDefaultAttribute()
	{
		$this->cb->BBCodes->addBBCode('X', array(
			'defaultAttr' => 'z'
		));

		$this->cb->addTagAttribute('X', 'z', 'text', array('isRequired' => false));

		$this->assertParsing(
			'[X="123"][/X]',
			'<rt><X z="123"><st>[X="123"]</st><et>[/X]</et></X></rt>'
		);
	}

	/**
	* @depends testABbcodeCanMapToATagOfADifferentName
	* @depends testAnEqualSignFollowingTheTagNameDefinesTheValueOfTheDefaultAttribute
	*/
	public function testIfNoDefaultAttributeIsSpecifiedTheNameOfTheBbcodeIsUsedAsTheNameOfTheDefaultAttribute()
	{
		$this->cb->BBCodes->addBBCode('X', array('tagName' => 'Z'));

		$this->cb->addTagAttribute('Z', 'x', 'text', array('isRequired' => false));

		$this->assertParsing(
			'[X="123"][/X]',
			'<rt><Z x="123"><st>[X="123"]</st><et>[/X]</et></Z></rt>'
		);
	}
}