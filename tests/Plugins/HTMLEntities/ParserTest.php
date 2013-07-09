<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLEntities;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\HTMLEntities\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLEntities\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return [
			[
				'AT&amp;T',
				'<rt>AT<HE char="&amp;">&amp;amp;</HE>T</rt>'
			],
			[
				'AT&amp;T',
				'<rt>AT<FOO char="&amp;">&amp;amp;</FOO>T</rt>',
				['tagName' => 'FOO']
			],
			[
				'AT&amp;T',
				'<rt>AT<HE bar="&amp;">&amp;amp;</HE>T</rt>',
				['attrName' => 'bar']
			],
			[
				'I &hearts; AT&amp;T',
				'<rt>I <HE char="♥">&amp;hearts;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</rt>'
			],
			[
				'I &#x2665; AT&amp;T',
				'<rt>I <HE char="♥">&amp;#x2665;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</rt>'
			],
			[
				'I &#9829; AT&amp;T',
				'<rt>I <HE char="♥">&amp;#9829;</HE> AT<HE char="&amp;">&amp;amp;</HE>T</rt>'
			],
			[
				'Some &unknown; entity',
				'<pt>Some &amp;unknown; entity</pt>'
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'AT&amp;T',
				'AT&amp;T',
			],
			[
				'AT&amp;T',
				'AT&amp;T',
				['tagName' => 'FOO']
			],
			[
				'AT&amp;T',
				'AT&amp;T',
				['attrName' => 'bar']
			],
			[
				'I &hearts; AT&amp;T',
				'I ♥ AT&amp;T'
			],
			[
				'Pok&eacute;man',
				'Pokéman'
			],
			[
				'POK&Eacute;MAN',
				'POKÉMAN'
			],
		];
	}
}