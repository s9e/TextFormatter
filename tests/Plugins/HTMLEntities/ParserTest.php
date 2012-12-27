<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLEntities;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\HTMLEntities\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLEntities\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				'AT&amp;T',
				'<rt>AT<HE char="&amp;">&amp;amp;</HE>T</rt>'
			),
			array(
				'AT&amp;T',
				'<rt>AT<FOO char="&amp;">&amp;amp;</FOO>T</rt>',
				array('tagName' => 'FOO')
			),
			array(
				'AT&amp;T',
				'<rt>AT<HE bar="&amp;">&amp;amp;</HE>T</rt>',
				array('attrName' => 'bar')
			),
			array(
				'I &hearts; AT&amp;T',
				'<rt>I <HE char="♥">&amp;hearts;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</rt>'
			),
			array(
				'I &#x2665; AT&amp;T',
				'<rt>I <HE char="♥">&amp;#x2665;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</rt>'
			),
			array(
				'I &#9829; AT&amp;T',
				'<rt>I <HE char="♥">&amp;#9829;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</rt>'
			),
			array(
				'Some &unknown; entity',
				'<pt>Some &amp;unknown; entity</pt>'
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'AT&amp;T',
				'AT&amp;T',
			),
			array(
				'AT&amp;T',
				'AT&amp;T',
				array('tagName' => 'FOO')
			),
			array(
				'AT&amp;T',
				'AT&amp;T',
				array('attrName' => 'bar')
			),
			array(
				'I &hearts; AT&amp;T',
				'I ♥ AT&amp;T'
			),
			array(
				'Pok&eacute;man',
				'Pokéman'
			),
			array(
				'POK&Eacute;MAN',
				'POKÉMAN'
			),
		);
	}
}